@extends('roles.layout')

@section('title', 'Incomes')

@section('content')
@php
    $sourceLabels = [
        'sales' => 'Sale',
        'stock_adjustments' => 'Stock adjustment',
        'productions' => 'Production',
    ];
@endphp
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Incomes</h2>
        <a href="{{ route('incomes.create') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Income
        </a>
    </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incomes as $income)
                    @php
                        $saleId = $income->table_name === 'sales' ? $income->transaction?->fk_reference_id : null;
                        $refunded = $saleId && isset($returnsBySale[$saleId]) ? (float) $returnsBySale[$saleId] : 0.0;
                    @endphp
                    <tr>
                        <td>{{ $income->id }}</td>
                        <td>
                            {{ $income->description }}
                            @if(isset($sourceLabels[$income->table_name]))
                                <span class="badge badge-blue" style="margin-left: 6px;">{{ $sourceLabels[$income->table_name] }}</span>
                            @endif
                            @if($refunded > 0)
                                <span class="badge badge-orange" style="margin-left: 6px;" title="This income is shown net of approved sale returns">
                                    Net of {{ $currencySymbol }}{{ number_format($refunded, 2) }} returns
                                </span>
                            @endif
                        </td>
                        <td style="color: var(--success, #22c55e); font-weight: 600;">{{ $currencySymbol }}{{ number_format($income->amount, 2) }}</td>
                        <td>{{ $income->transaction->date ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">No incomes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($incomes->hasPages())
        <div style="padding: 16px;">
            {{ $incomes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
