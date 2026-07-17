@extends('roles.layout')

@section('title', 'Accounts Payable')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Accounts Payable</h2>
    </div>

    <div style="padding: 16px; border-bottom: 1px solid var(--border);">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: #fef2f2; border-radius: 8px;">
            <span style="font-size: 14px; color: var(--text-secondary);">Total Payable:</span>
            <strong style="font-size: 20px; color: var(--error);">{{ $currencySymbol }}{{ number_format($totalPayable, 2) }}</strong>
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
                @forelse($payables as $payable)
                    <tr>
                        <td>{{ $payable->id }}</td>
                        <td><span class="badge badge-orange">{{ $payable->table_name }}</span></td>
                        <td>{{ $payable->description }}</td>
                        <td style="color: var(--error); font-weight: 600;">{{ $currencySymbol }}{{ number_format($payable->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">No payables found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($payables->hasPages())
        <div style="padding: 16px;">
            {{ $payables->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
