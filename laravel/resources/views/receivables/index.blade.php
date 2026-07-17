@extends('roles.layout')

@section('title', 'Accounts Receivable')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Accounts Receivable</h2>
    </div>

    <div style="padding: 16px; border-bottom: 1px solid var(--border);">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: #f0fdf4; border-radius: 8px;">
            <span style="font-size: 14px; color: var(--text-secondary);">Total Receivable:</span>
            <strong style="font-size: 20px; color: var(--success, #22c55e);">{{ $currencySymbol }}{{ number_format($totalReceivable, 2) }}</strong>
        </div>
    </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Table</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receivables as $receivable)
                    <tr>
                        <td>{{ $receivable->id }}</td>
                        <td><span class="badge badge-green">{{ $receivable->table_name }}</span></td>
                        <td>{{ $receivable->description }}</td>
                        <td style="color: var(--success, #22c55e); font-weight: 600;">{{ $currencySymbol }}{{ number_format($receivable->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">No receivables found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($receivables->hasPages())
        <div style="padding: 16px;">
            {{ $receivables->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
