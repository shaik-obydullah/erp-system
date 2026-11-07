@extends('roles.layout')

@section('title', 'Accounting — Profit & Loss')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Profit &amp; Loss</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <form method="GET" action="{{ route('accounting.profit-loss') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end; border-bottom:1px solid var(--border);">
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">From
            <input type="date" name="from" value="{{ $from }}" class="form-control">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">To
            <input type="date" name="to" value="{{ $to }}" class="form-control">
        </label>
        <button type="submit" class="btn btn-primary">Show</button>
    </form>

    <table>
        <thead>
            <tr><th>Code</th><th>Revenue account</th><th style="text-align:right;">Amount</th></tr>
        </thead>
        <tbody>
            @foreach($revenue as $row)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->balance, 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight:700; background:var(--bg-secondary, #f8fafc);">
                <td colspan="2">Total revenue</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($totalRevenue, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th>Code</th><th>Expense account</th><th style="text-align:right;">Amount</th></tr>
        </thead>
        <tbody>
            @foreach($expenses as $row)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->balance, 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight:700; background:var(--bg-secondary, #f8fafc);">
                <td colspan="2">Total expenses</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($totalExpense, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="padding:16px; font-size:18px; font-weight:700; color:{{ $netProfit >= 0 ? '#15803d' : '#b91c1c' }};">
        Net {{ $netProfit >= 0 ? 'profit' : 'loss' }}: {{ $currencySymbol }}{{ number_format(abs($netProfit), 2) }}
    </div>
</div>
@endsection
