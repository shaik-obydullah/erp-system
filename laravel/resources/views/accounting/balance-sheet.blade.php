@extends('roles.layout')

@section('title', 'Accounting — Balance Sheet')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Balance Sheet</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <form method="GET" action="{{ route('accounting.balance-sheet') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end; border-bottom:1px solid var(--border);">
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">As of
            <input type="date" name="to" value="{{ $to }}" class="form-control">
        </label>
        <button type="submit" class="btn btn-primary">Show</button>
    </form>

    <div style="padding:16px; display:grid; grid-template-columns:repeat(2, 1fr); gap:16px;">
        <div>
            <h3 style="font-weight:700; margin-bottom:8px;">Assets</h3>
            <table>
                <tbody>
                    @forelse($assets as $row)
                        <tr>
                            <td>{{ $row->code }} — {{ $row->name }}</td>
                            <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="empty-state">No asset activity.</td></tr>
                    @endforelse
                    <tr style="font-weight:700; background:var(--bg-secondary, #f8fafc);">
                        <td>Total assets</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($totalAssets, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h3 style="font-weight:700; margin-bottom:8px;">Liabilities &amp; Equity</h3>
            <table>
                <tbody>
                    @foreach($liabilities as $row)
                        <tr>
                            <td>{{ $row->code }} — {{ $row->name }}</td>
                            <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    @foreach($equity as $row)
                        <tr>
                            <td>{{ $row->code }} — {{ $row->name }}</td>
                            <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td>Current period earnings</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($currentEarnings, 2) }}</td>
                    </tr>
                    <tr style="font-weight:700; background:var(--bg-secondary, #f8fafc);">
                        <td>Total liabilities &amp; equity</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($totalLiabilities + $totalEquity, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="padding:16px; font-weight:600; color:{{ round($totalAssets, 2) === round($totalLiabilities + $totalEquity, 2) ? '#15803d' : '#b91c1c' }};">
        {{ round($totalAssets, 2) === round($totalLiabilities + $totalEquity, 2) ? '✓ Assets equal liabilities plus equity.' : '✗ The balance sheet does not balance.' }}
    </div>
</div>
@endsection
