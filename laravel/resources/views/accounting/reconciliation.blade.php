@extends('roles.layout')

@section('title', 'Accounting — Reconciliation')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Reconciliation</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    <div style="padding:16px; color:var(--text-secondary); font-size:13px;">
        These checks compare what the simple cashbook and party balances say against the double-entry ledger.
        Small differences appear for older rows entered before the ledger was enabled.
    </div>

    @php
        $rows = [
            ['label' => 'Cashbook net (in − out)', 'left' => $cashbookNet, 'right' => $cashAccount, 'rightLabel' => 'Cash account balance'],
            ['label' => 'Customer due balances', 'left' => $customerBalances, 'right' => $receivable, 'rightLabel' => 'Customer-attributed receivable'],
            ['label' => 'Supplier due balances', 'left' => $supplierBalances, 'right' => $payable, 'rightLabel' => 'Supplier-attributed payable'],
        ];
    @endphp

    <table>
        <thead>
            <tr>
                <th>Check</th>
                <th style="text-align:right;">Simple layer</th>
                <th style="text-align:right;">Ledger</th>
                <th style="text-align:right;">Difference</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php $difference = round($row['left'] - $row['right'], 2); @endphp
                <tr>
                    <td>
                        {{ $row['label'] }}
                        <div style="font-size:12px; color:var(--text-secondary);">vs {{ $row['rightLabel'] }}</div>
                    </td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row['left'], 2) }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($row['right'], 2) }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($difference, 2) }}</td>
                    <td style="color:{{ abs($difference) < 0.01 ? '#15803d' : '#b45309' }};">
                        {{ abs($difference) < 0.01 ? '✓ Matched' : 'Needs review' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(abs($unassignedReceivable) >= 0.01 || abs($unassignedPayable) >= 0.01)
        <div style="padding:16px; color:var(--text-secondary); font-size:13px; border-top:1px solid var(--border-color);">
            <strong>Not assigned to a party:</strong>
            receivable {{ $currencySymbol }}{{ number_format($unassignedReceivable, 2) }},
            payable {{ $currencySymbol }}{{ number_format($unassignedPayable, 2) }}.
            These come from documents (for example POS sales) that have no customer or supplier attached,
            so they stay on the control account but have no subledger row to reconcile against.
        </div>
    @endif
</div>
@endsection
