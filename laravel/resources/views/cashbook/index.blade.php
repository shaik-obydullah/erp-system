@extends('roles.layout')

@section('title', 'Cashbook')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Cashbook</h2>
    </div>

    <div style="padding: 16px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; border-bottom: 1px solid var(--border);">
        <div style="padding: 16px; background: var(--bg-secondary, #f0fdf4); border-radius: 8px; text-align: center;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Total In</div>
            <div style="font-size: 20px; font-weight: 700; color: var(--success, #22c55e);">{{ $currencySymbol }}{{ number_format($totalIn, 2) }}</div>
        </div>
        <div style="padding: 16px; background: #fef2f2; border-radius: 8px; text-align: center;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Total Out</div>
            <div style="font-size: 20px; font-weight: 700; color: var(--error);">{{ $currencySymbol }}{{ number_format($totalOut, 2) }}</div>
        </div>
        <div style="padding: 16px; background: #eff6ff; border-radius: 8px; text-align: center;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Total Payable</div>
            <div style="font-size: 20px; font-weight: 700; color: #2563eb;">{{ $currencySymbol }}{{ number_format($totalPayable, 2) }}</div>
        </div>
        <div style="padding: 16px; background: #f5f3ff; border-radius: 8px; text-align: center;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Total Receivable</div>
            <div style="font-size: 20px; font-weight: 700; color: #7c3aed;">{{ $currencySymbol }}{{ number_format($totalReceivable, 2) }}</div>
        </div>
    </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>In Amount</th>
                    <th>Out Amount</th>
                    <th>Amount Payable</th>
                    <th>Amount Receivable</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->id }}</td>
                        <td>{{ $entry->description }}</td>
                        <td style="color: var(--success, #22c55e); font-weight: 600;">{{ $entry->in_amount > 0 ? $currencySymbol . number_format($entry->in_amount, 2) : '—' }}</td>
                        <td style="color: var(--error); font-weight: 600;">{{ $entry->out_amount > 0 ? $currencySymbol . number_format($entry->out_amount, 2) : '—' }}</td>
                        <td>{{ $entry->amount_payable > 0 ? $currencySymbol . number_format($entry->amount_payable, 2) : '—' }}</td>
                        <td>{{ $entry->amount_receivable > 0 ? $currencySymbol . number_format($entry->amount_receivable, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No cashbook entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($entries->hasPages())
        <div style="padding: 16px;">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
