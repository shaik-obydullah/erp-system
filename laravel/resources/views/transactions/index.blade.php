@extends('roles.layout')

@section('title', 'Transactions')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Transaction Log</h2>
    </div>

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('transactions.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <select name="type" class="form-input" style="max-width: 200px;">
                <option value="">All Types</option>
                <option value="saleIncome" {{ request('type') === 'saleIncome' ? 'selected' : '' }}>Sale Income</option>
                <option value="saleDue" {{ request('type') === 'saleDue' ? 'selected' : '' }}>Sale Due</option>
                <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                <option value="shipmentCost" {{ request('type') === 'shipmentCost' ? 'selected' : '' }}>Shipment Cost</option>
                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                <option value="purchaseReturn" {{ request('type') === 'purchaseReturn' ? 'selected' : '' }}>Purchase Return</option>
                <option value="supplierPayment" {{ request('type') === 'supplierPayment' ? 'selected' : '' }}>Supplier Payment</option>
                <option value="supplierDeposit" {{ request('type') === 'supplierDeposit' ? 'selected' : '' }}>Supplier Advance</option>
                <option value="userFund" {{ request('type') === 'userFund' ? 'selected' : '' }}>Customer Fund</option>
                <option value="miscExpense" {{ request('type') === 'miscExpense' ? 'selected' : '' }}>Misc Expense</option>
                <option value="stockAdjustmentLoss" {{ request('type') === 'stockAdjustmentLoss' ? 'selected' : '' }}>Stock Adjustment Loss</option>
                <option value="stockAdjustmentGain" {{ request('type') === 'stockAdjustmentGain' ? 'selected' : '' }}>Stock Adjustment Return</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->has('type'))
                <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Paid Amount</th>
                    <th>Due Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->id }}</td>
                        <td>{{ $transaction->date ? \Carbon\Carbon::parse($transaction->date)->format('d M Y') : '—' }}</td>
                        <td><span class="badge badge-orange">{{ $transaction->typeLabel() }}</span></td>
                        <td>{{ $currencySymbol }}{{ number_format($transaction->amount, 2) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($transaction->paid_amount, 2) }}</td>
                        <td>
                            <span style="color: {{ $transaction->due_amount > 0 ? 'var(--error)' : 'var(--success, #22c55e)' }}; font-weight: 600;">
                                {{ $currencySymbol }}{{ number_format($transaction->due_amount, 2) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($transactions->hasPages())
        <div style="padding: 16px;">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
