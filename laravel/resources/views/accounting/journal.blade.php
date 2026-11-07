@extends('roles.layout')

@section('title', 'Accounting — Journal')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Journal</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <form method="GET" action="{{ route('accounting.journal') }}" style="padding:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end; border-bottom:1px solid var(--border);">
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
            From
            <input type="date" name="from" value="{{ $from }}" class="form-control">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
            To
            <input type="date" name="to" value="{{ $to }}" class="form-control">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px; font-size:12px; min-width:220px;">
            Search
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Entry no or memo" class="form-control">
        </label>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Entry</th>
                <th>Date</th>
                <th>Account</th>
                <th>Description</th>
                <th style="text-align:right;">Debit</th>
                <th style="text-align:right;">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr style="background:var(--bg-secondary, #f8fafc);">
                    <td style="font-weight:700;">{{ $entry->entry_no }}</td>
                    <td>{{ $entry->date?->format('Y-m-d') }}</td>
                    <td colspan="2" style="font-weight:600;">{{ $entry->memo }} <span style="font-weight:400; color:var(--text-secondary);">({{ $entry->event_type }})</span></td>
                    <td style="text-align:right; font-weight:600;">{{ $currencySymbol }}{{ number_format($entry->debit_total, 2) }}</td>
                    <td style="text-align:right; font-weight:600;">{{ $currencySymbol }}{{ number_format($entry->credit_total, 2) }}</td>
                </tr>
                @foreach($entry->lines as $line)
                    <tr>
                        <td></td>
                        <td></td>
                        <td style="padding-left:24px;">{{ $line->account?->code }} — {{ $line->account?->name }}</td>
                        <td>{{ $line->description }}</td>
                        <td style="text-align:right;">{{ (float) $line->debit > 0 ? $currencySymbol . number_format($line->debit, 2) : '—' }}</td>
                        <td style="text-align:right;">{{ (float) $line->credit > 0 ? $currencySymbol . number_format($line->credit, 2) : '—' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="empty-state">No journal entries in this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($entries->hasPages())
        <div style="padding:16px;">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
