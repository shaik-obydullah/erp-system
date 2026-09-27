@extends('roles.layout')

@section('title', 'Expense Report')

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
        <a href="{{ route('reports.expense') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px; background: var(--bg-white); color: var(--primary); border-bottom: 2px solid var(--primary);">
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
        <form method="GET" action="{{ route('reports.expense') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-input" value="{{ $startDate }}" style="width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-input" value="{{ $endDate }}" style="width: 100%;">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
            @if(request()->hasAny(['start_date', 'end_date']))
                <a href="{{ route('reports.expense') }}" class="btn btn-ghost" style="height: 42px;">Clear</a>
            @endif
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="stat-card">
        <div class="stat-icon red">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Expenses</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalExpenses, 2) }}</span>
        </div>
    </div>
</div>

<div style="margin-bottom: 28px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Monthly Expenses</h2>
        </div>
        <div class="card-body">
            <canvas id="monthlyExpenseChart" height="100"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Expense Details</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td><strong>{{ $expense->id }}</strong></td>
                    <td>{{ $expense->transaction->date ?? '—' }}</td>
                    <td>{{ $expense->description ?? '—' }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($expense->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">No expenses found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($expenses->hasPages())
    <div style="padding: 16px;">
        {{ $expenses->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('monthlyExpenseChart');
        if (!ctx) return;

        var labels = {!! $chartLabelsJson !!};
        var data = {!! $monthlyExpensesJson !!};

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Expenses',
                    data: data,
                    backgroundColor: 'rgba(234, 67, 53, 0.15)',
                    borderColor: '#ea4335',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(234, 67, 53, 0.3)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ea4335',
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
