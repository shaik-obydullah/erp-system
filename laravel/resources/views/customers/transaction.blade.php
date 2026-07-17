@extends('roles.layout')

@section('title', 'Customer Transactions')

@section('content')
<div class="card" x-data="transactionFilters()" 
     data-customer-id="{{ request('customer_id') }}" 
     data-date-from="{{ request('date_from') }}" 
     data-date-to="{{ request('date_to') }}" 
     data-clear-url="{{ route('customers.transaction.index') }}">
    <div class="card-header">
        <h2 class="card-title">Customer Transaction History</h2>
    </div>

    @if(session('success'))
    <div x-data="flashMessage()" x-show="show" x-transition x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
        {{ session('success') }}
    </div>
    @endif

    <!-- Filters -->
    <div style="padding: 16px;">
        <form method="GET" action="{{ route('customers.transaction.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <select name="customer_id" class="form-input" style="max-width: 250px;" x-model="customerId">
                <option value="">All Customers</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" class="form-input" style="max-width: 180px;" x-model="dateFrom">
            <input type="date" name="date_to" class="form-input" style="max-width: 180px;" x-model="dateTo">
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['customer_id', 'date_from', 'date_to']))
                <button type="button" class="btn btn-ghost" @click="clear()">Clear</button>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Description</th>
                <th>In Amount</th>
                <th>Out Amount</th>
                <th>Payable</th>
                <th>Receivable</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $txn)
                <tr>
                    <td>#{{ $txn->id }}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                {{ $txn->reference ? substr($txn->reference->name, 0, 1) : '?' }}
                            </div>
                            <strong>{{ $txn->reference->name ?? 'N/A' }}</strong>
                        </div>
                    </td>
                    <td>{{ $txn->description ?? '—' }}</td>
                    <td style="color: var(--success); font-weight: 500;">
                        @if($txn->in_amount > 0)
                            {{ $currencySymbol }}{{ number_format($txn->in_amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="color: var(--error); font-weight: 500;">
                        @if($txn->out_amount > 0)
                            {{ $currencySymbol }}{{ number_format($txn->out_amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($txn->amount_payable > 0)
                            {{ $currencySymbol }}{{ number_format($txn->amount_payable, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($txn->amount_receivable > 0)
                            {{ $currencySymbol }}{{ number_format($txn->amount_receivable, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $txn->created_at ? $txn->created_at->format('M d, Y') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-state">No transactions found.</td>
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
@endsection
