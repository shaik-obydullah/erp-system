@extends('roles.layout')

@section('title', 'Accounting — General Ledger')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">General Ledger</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <form method="GET" action="{{ route('accounting.ledger') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end; border-bottom:1px solid var(--border);">
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px; min-width:260px;">
            Account
            <select name="account_id" class="form-control">
                @foreach($accounts as $item)
                    <option value="{{ $item->id }}" @selected($account && $account->id === $item->id)>
                        {{ $item->code }} — {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
            From
            <input type="date" name="from" value="{{ $from }}" class="form-control">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
            To
            <input type="date" name="to" value="{{ $to }}" class="form-control">
        </label>
        <button type="submit" class="btn btn-primary">Show</button>
    </form>

    @if($account)
        <div style="padding:16px; display:flex; gap:24px; flex-wrap:wrap; border-bottom:1px solid var(--border);">
            <div><span style="color:var(--text-secondary); font-size:12px;">Account</span><div style="font-weight:700;">{{ $account->code }} — {{ $account->name }}</div></div>
            <div><span style="color:var(--text-secondary); font-size:12px;">Opening</span><div style="font-weight:700;">{{ $currencySymbol }}{{ number_format($opening, 2) }}</div></div>
            <div><span style="color:var(--text-secondary); font-size:12px;">Closing</span><div style="font-weight:700;">{{ $currencySymbol }}{{ number_format($closing, 2) }}</div></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Description</th>
                    <th style="text-align:right;">Debit</th>
                    <th style="text-align:right;">Credit</th>
                    <th style="text-align:right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:var(--bg-secondary, #f8fafc);">
                    <td colspan="5" style="font-weight:600;">Opening balance</td>
                    <td style="text-align:right; font-weight:600;">{{ $currencySymbol }}{{ number_format($opening, 2) }}</td>
                </tr>
                @php $running = $opening; @endphp
                @forelse($lines as $line)
                    @php $running += (float) $line->debit - (float) $line->credit; @endphp
                    <tr>
                        <td>{{ $line->entry->date?->format('Y-m-d') }}</td>
                        <td>{{ $line->entry->entry_no }}</td>
                        <td>{{ $line->description ?: $line->entry->memo }}</td>
                        <td style="text-align:right;">{{ (float) $line->debit > 0 ? $currencySymbol . number_format($line->debit, 2) : '—' }}</td>
                        <td style="text-align:right;">{{ (float) $line->credit > 0 ? $currencySymbol . number_format($line->credit, 2) : '—' }}</td>
                        <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($running, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No movements in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <div style="padding:16px;" class="empty-state">No accounts found. Run the chart-of-accounts seeder.</div>
    @endif
</div>
@endsection
