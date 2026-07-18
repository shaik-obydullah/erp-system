@extends('roles.layout')

@section('title', 'Sales Report')

@section('content')
<div style="margin-bottom: 24px;">
    <div style="display: flex; gap: 4px; border-bottom: 1px solid var(--border); padding: 0 16px;">
        <a href="{{ route('reports.index') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Overview
        </a>
        <a href="{{ route('reports.sales') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px; background: var(--bg-white); color: var(--primary); border-bottom: 2px solid var(--primary);">
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

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.sales') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-input" value="{{ $startDate }}" style="width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-input" value="{{ $endDate }}" style="width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="type">Type</label>
                <select name="type" id="type" class="form-input" style="width: 100%;">
                    <option value="">All Types</option>
                    <option value="pos" {{ $type === 'pos' ? 'selected' : '' }}>POS</option>
                    <option value="online" {{ $type === 'online' ? 'selected' : '' }}>Online</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
            @if(request()->hasAny(['start_date', 'end_date', 'type']))
                <a href="{{ route('reports.sales') }}" class="btn btn-ghost" style="height: 42px;">Clear</a>
            @endif
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Sales</span>
            <span class="stat-value">{{ number_format($totalSalesCount) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalRevenue, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Paid</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalPaid, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Due</span>
            <span class="stat-value" style="color: {{ $totalDue > 0 ? 'var(--error)' : 'var(--success, #1e8e3e)' }};">{{ $currencySymbol }}{{ number_format($totalDue, 2) }}</span>
        </div>
    </div>
</div>

<div style="margin-bottom: 28px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Monthly Sales</h2>
        </div>
        <div class="card-body">
            <canvas id="monthlySalesChart" height="100"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Sales Details</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Invoice ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Customer</th>
                <th>Grand Total</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td><strong>{{ $sale->invoice_id }}</strong></td>
                    <td>{{ $sale->transaction->date ?? '—' }}</td>
                    <td><span class="badge badge-orange">{{ ucfirst($sale->type) }}</span></td>
                    <td>{{ $sale->customer->name ?? '—' }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</td>
                    <td>
                        <span style="color: {{ $sale->sale_due > 0 ? 'var(--error)' : 'var(--success, #1e8e3e)' }}; font-weight: 600;">
                            {{ $currencySymbol }}{{ number_format($sale->sale_due, 2) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $sale->status === 'paid' ? 'badge-green' : ($sale->status === 'partial' ? 'badge-orange' : 'badge-blue') }}">
                            {{ ucfirst($sale->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-state">No sales found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($sales->hasPages())
    <div style="padding: 16px;">
        {{ $sales->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('monthlySalesChart');
        if (!ctx) return;

        var labels = {!! $chartLabelsJson !!};
        var totals = {!! $monthlySalesJson !!};

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: totals,
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
