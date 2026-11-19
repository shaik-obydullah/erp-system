@extends('roles.layout')

@section('title', 'Accounting — Cash Flow')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Cash Flow</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <form method="GET" action="{{ route('accounting.cash-flow') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end; border-bottom:1px solid var(--border);">
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
                <th>Activity</th>
                <th style="text-align:right;">In</th>
                <th style="text-align:right;">Out</th>
                <th style="text-align:right;">Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['operating' => 'Operating activities', 'investing' => 'Investing activities', 'financing' => 'Financing activities'] as $key => $label)
                @php $group = $groups[$key]; @endphp
                <tr style="background:var(--bg-secondary, #f8fafc);">
                    <td colspan="4" style="font-weight:700;">{{ $label }}</td>
                </tr>
                @forelse($group as $row)
                    <tr>
                        <td style="padding-left:24px;">{{ $row->event_type }}</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->inflow, 2) }}</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->outflow, 2) }}</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row->net, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state" style="padding-left:24px;">No movement.</td></tr>
                @endforelse
                <tr style="font-weight:600;">
                    <td>Net {{ strtolower($label) }}</td>
                    <td colspan="2"></td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($group->sum('net'), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700;">
                <td>Opening cash &amp; bank</td>
                <td colspan="2"></td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($opening, 2) }}</td>
            </tr>
            <tr style="font-weight:700;">
                <td>Net movement</td>
                <td colspan="2"></td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($netMovement, 2) }}</td>
            </tr>
            <tr style="font-weight:700; background:var(--bg-secondary, #f8fafc);">
                <td>Closing cash &amp; bank</td>
                <td colspan="2"></td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($closing, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
