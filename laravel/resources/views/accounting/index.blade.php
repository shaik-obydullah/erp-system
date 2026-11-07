@extends('roles.layout')

@section('title', 'Accounting')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Accounting</h2>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @if(auth('admin')->user()?->hasPermission('accounting.advanced'))
                <a href="{{ route('accounting.journal') }}" class="btn btn-secondary">Journal</a>
                <a href="{{ route('accounting.ledger') }}" class="btn btn-secondary">General Ledger</a>
                <a href="{{ route('accounting.trial-balance') }}" class="btn btn-secondary">Trial Balance</a>
                <a href="{{ route('accounting.profit-loss') }}" class="btn btn-secondary">Profit &amp; Loss</a>
                <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-secondary">Balance Sheet</a>
                <a href="{{ route('accounting.cash-flow') }}" class="btn btn-secondary">Cash Flow</a>
            @endif
        </div>
    </div>

    <div style="padding:16px; border-bottom:1px solid var(--border);">
        <div style="padding:14px 16px; border-radius:8px; font-weight:600;
            background: {{ $balanced ? '#f0fdf4' : '#fef2f2' }};
            color: {{ $balanced ? '#15803d' : '#b91c1c' }};">
            @if($balanced)
                ✓ Your books are balanced — debits equal credits.
            @else
                ⚠ Your books are not balanced. Debit {{ number_format($totalDebit, 2) }} vs credit {{ number_format($totalCredit, 2) }}.
                <a href="{{ route('accounting.reconciliation') }}" style="margin-left:8px; text-decoration:underline;">Check reconciliation</a>
            @endif
        </div>
    </div>

    <div style="padding:16px; display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
        <div style="padding:16px; background:#f0fdf4; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Money in (cashbook)</div>
            <div style="font-size:20px; font-weight:700; color:#15803d;">{{ $currencySymbol }}{{ number_format($moneyIn, 2) }}</div>
        </div>
        <div style="padding:16px; background:#fef2f2; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Money out (cashbook)</div>
            <div style="font-size:20px; font-weight:700; color:#b91c1c;">{{ $currencySymbol }}{{ number_format($moneyOut, 2) }}</div>
        </div>
        <div style="padding:16px; background:#eff6ff; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Cash balance (ledger)</div>
            <div style="font-size:20px; font-weight:700; color:#1d4ed8;">{{ $currencySymbol }}{{ number_format($cashBalance, 2) }}</div>
        </div>
        <div style="padding:16px; background:#f5f3ff; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customers owe you</div>
            <div style="font-size:20px; font-weight:700; color:#6d28d9;">{{ $currencySymbol }}{{ number_format($receivable, 2) }}</div>
        </div>
        <div style="padding:16px; background:#fffbeb; border-radius:8px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">You owe suppliers</div>
            <div style="font-size:20px; font-weight:700; color:#b45309;">{{ $currencySymbol }}{{ number_format($payable, 2) }}</div>
        </div>
        <div style="padding:16px; background:#f8fafc; border-radius:8px; border:1px solid var(--border);">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tax / VAT payable</div>
            <div style="font-size:20px; font-weight:700;">{{ $currencySymbol }}{{ number_format($taxPayable, 2) }}</div>
        </div>
    </div>

    <div style="padding:0 16px 8px; display:flex; gap:16px; flex-wrap:wrap; color:var(--text-secondary); font-size:13px;">
        <span>{{ $entryCount }} journal entries</span>
        <span>·</span>
        <span>{{ $accountCount }} accounts</span>
        @if(auth('admin')->user()?->hasPermission('accounting.advanced'))
        <span>·</span>
        <a href="{{ route('accounting.chart-of-accounts') }}" style="text-decoration:underline;">View chart of accounts</a>
        <span>·</span>
        <a href="{{ route('accounting.reconciliation') }}" style="text-decoration:underline;">Reconciliation</a>
        @endif
    </div>

    <div style="padding:16px; border-bottom:1px solid var(--border);">
        <h3 style="font-size:14px; font-weight:700; margin-bottom:8px;">Books health</h3>
        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <div style="padding:10px 14px; border-radius:8px; font-size:13px; font-weight:600;
                background: {{ $health['healthy'] ? '#f0fdf4' : '#fef2f2' }};
                color: {{ $health['healthy'] ? '#15803d' : '#b91c1c' }};">
                {{ $health['healthy'] ? '✓ All checks passing' : '⚠ Issues need attention' }}
            </div>
            <div style="font-size:13px; color:var(--text-secondary);">
                Unbalanced entries: <strong>{{ $health['unbalanced_entries'] }}</strong>
                · Broken entries: <strong>{{ $health['broken_entries'] }}</strong>
                · Cash difference: <strong>{{ number_format($health['cash_difference'], 2) }}</strong>
                · Receivable difference: <strong>{{ number_format($health['receivable_difference'], 2) }}</strong>
                · Payable difference: <strong>{{ number_format($health['payable_difference'], 2) }}</strong>
            </div>
        </div>
    </div>

    <div style="padding:16px;">
        <h3 style="font-size:14px; font-weight:700; margin-bottom:8px;">Recent journal entries</h3>
        <table>
            <thead>
                <tr>
                    <th>Entry</th>
                    <th>Date</th>
                    <th>Memo</th>
                    <th style="text-align:right;">Debit</th>
                    <th style="text-align:right;">Credit</th>
                    <th>Balanced</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->entry_no }}</td>
                        <td>{{ $entry->date?->format('Y-m-d') }}</td>
                        <td>{{ $entry->memo }}</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($entry->debit_total, 2) }}</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($entry->credit_total, 2) }}</td>
                        <td style="color:{{ $entry->is_balanced ? '#15803d' : '#b91c1c' }};">{{ $entry->is_balanced ? '✓' : '✗' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No journal entries yet. New sales, income and expenses will appear here automatically.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
