@extends('roles.layout')

@section('title', 'Cashbook')

@section('content')
@php
    $typeLabels = [
        'sales' => 'Sale',
        'sale_returns' => 'Sale return',
        'customers' => 'Customer',
        'suppliers' => 'Supplier',
        'expenses' => 'Expense',
        'incomes' => 'Income',
        'purchases' => 'Purchase',
        'shipment_costs' => 'Shipping cost',
        'shipments' => 'Shipment',
        'shipment_returns' => 'Shipment return',
        'payrolls' => 'Payroll',
        'campaigns' => 'Campaign',
        'productions' => 'Production',
        'stock_adjustments' => 'Stock adjustment',
        'fixed_assets' => 'Fixed asset',
    ];
@endphp

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
            <h2 class="card-title" style="margin-bottom:4px;">Cashbook</h2>
            <p style="font-size:13px; color:var(--text-secondary); max-width:640px;">
                Every payment that actually came in or went out of your business, in the order it was recorded.
                This is <strong>not</strong> a balance sheet — it shows cash movement, not what your business is worth.
            </p>
        </div>
        @if(auth('admin')->user()?->hasPermission('accounting.advanced'))
            <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-secondary">View Balance Sheet</a>
        @endif
    </div>

    <div style="padding:16px; display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; border-bottom:1px solid var(--border);">
        <div style="padding:16px; background:#f0fdf4; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Cash in</div>
            <div style="font-size:22px; font-weight:700; color:#15803d;">{{ $currencySymbol }}{{ number_format($totalIn, 2) }}</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Money received</div>
        </div>
        <div style="padding:16px; background:#fef2f2; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Cash out</div>
            <div style="font-size:22px; font-weight:700; color:#b91c1c;">{{ $currencySymbol }}{{ number_format($totalOut, 2) }}</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Money paid out</div>
        </div>
        <div style="padding:16px; background:{{ $netCash < 0 ? '#fef2f2' : '#eff6ff' }}; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Net cash movement</div>
            <div style="font-size:22px; font-weight:700; color:{{ $netCash < 0 ? '#b91c1c' : '#1d4ed8' }};">{{ $currencySymbol }}{{ number_format($netCash, 2) }}</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Cash in minus cash out</div>
        </div>
    </div>

    <div style="padding:16px; border-bottom:1px solid var(--border); background:var(--bg-secondary, #f8fafc);">
        <div style="font-size:13px; font-weight:700; margin-bottom:2px;">Money owed — not cash</div>
        <p style="font-size:12px; color:var(--text-secondary); margin-bottom:12px;">
            These are outstanding balances. They are shown for information only and are not included in the cash totals above.
        </p>
        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:16px;">
            <div style="padding:12px 16px; background:#fff; border:1px solid var(--border); border-radius:8px;">
                <div style="font-size:12px; color:var(--text-secondary);">Owed to you</div>
                <div style="font-size:18px; font-weight:700; color:#7c3aed;">{{ $currencySymbol }}{{ number_format($totalReceivable, 2) }}</div>
            </div>
            <div style="padding:12px 16px; background:#fff; border:1px solid var(--border); border-radius:8px;">
                <div style="font-size:12px; color:var(--text-secondary);">You owe</div>
                <div style="font-size:18px; font-weight:700; color:#2563eb;">{{ $currencySymbol }}{{ number_format($totalPayable, 2) }}</div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('cashbook.index') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; border-bottom:1px solid var(--border);">
        <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by description..." style="flex:1; min-width:220px;">
        <button type="submit" class="btn btn-primary">Search</button>
        @if(request('search'))
            <a href="{{ route('cashbook.index') }}" class="btn btn-secondary">Clear</a>
        @endif
    </form>

    <table>
        <thead>
            <tr>
                <th>Ref</th>
                <th>Description</th>
                <th>Type</th>
                <th style="text-align:right;">Cash in</th>
                <th style="text-align:right;">Cash out</th>
                <th style="text-align:right;">Owed to you</th>
                <th style="text-align:right;">You owe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td style="color:var(--text-secondary);">#{{ $entry->id }}</td>
                    <td>{{ $entry->description ?: '—' }}</td>
                    <td>{{ $typeLabels[$entry->table_name] ?? ucwords(str_replace('_', ' ', $entry->table_name)) }}</td>
                    <td style="text-align:right; color:#15803d; font-weight:600;">{{ $entry->in_amount > 0 ? $currencySymbol . number_format($entry->in_amount, 2) : '—' }}</td>
                    <td style="text-align:right; color:#b91c1c; font-weight:600;">{{ $entry->out_amount > 0 ? $currencySymbol . number_format($entry->out_amount, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ $entry->amount_receivable != 0 ? $currencySymbol . number_format($entry->amount_receivable, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ $entry->amount_payable != 0 ? $currencySymbol . number_format($entry->amount_payable, 2) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">No cashbook entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($entries->hasPages())
        <div style="padding:16px;">
            {{ $entries->links() }}
        </div>
    @endif
</div>
@endsection
