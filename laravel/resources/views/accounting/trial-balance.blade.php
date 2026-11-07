@extends('roles.layout')

@section('title', 'Accounting — Trial Balance')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Trial Balance</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <form method="GET" action="{{ route('accounting.trial-balance') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end; border-bottom:1px solid var(--border);">
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">From
            <input type="date" name="from" value="{{ $from }}" class="form-input">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">To
            <input type="date" name="to" value="{{ $to }}" class="form-input">
        </label>
        <button type="submit" class="btn btn-primary">Show</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account</th>
                <th>Type</th>
                <th style="text-align:right;">Debit</th>
                <th style="text-align:right;">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($balances as $row)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td style="text-transform:capitalize;">{{ $row->type }}</td>
                    <td style="text-align:right;">{{ (float) $row->debit > 0 ? $currencySymbol . number_format($row->debit, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ (float) $row->credit > 0 ? $currencySymbol . number_format($row->credit, 2) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">No ledger activity in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight:700; background:var(--bg-secondary, #f8fafc);">
                <td colspan="3">Total</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($totalDebit, 2) }}</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($totalCredit, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="padding:16px; font-weight:600; color:{{ round($totalDebit, 2) === round($totalCredit, 2) ? '#15803d' : '#b91c1c' }};">
        {{ round($totalDebit, 2) === round($totalCredit, 2) ? '✓ Debits equal credits.' : '✗ Debits and credits do not match.' }}
    </div>
</div>
@endsection
