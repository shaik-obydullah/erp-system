@extends('roles.layout')

@section('title', 'Customer Transactions')

@section('content')
<div x-data="{ showImportModal: false, closeImportModal() { this.showImportModal = false; } }">
<div class="card" x-data="transactionFilters()" 
     data-customer-id="{{ request('customer_id') }}" 
     data-date-from="{{ request('date_from') }}" 
     data-date-to="{{ request('date_to') }}" 
     data-clear-url="{{ route('customers.transaction.index') }}">
    <div class="card-header">
        <h2 class="card-title">Customer Transaction History</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('customers.transaction.export', request()->query()) }}" class="btn btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </a>
            <button type="button" class="btn btn-secondary" @click="showImportModal = true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import
            </button>
        </div>
    </div>

    @if(session('success'))
    <div x-data="flashMessage()" x-show="show" x-transition x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div x-data="flashMessage()" x-show="show" x-transition x-cloak class="alert alert-error show" style="margin: 16px 16px 0;">
        {{ session('error') }}
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
                                {{ $txn->customer ? substr($txn->customer->name, 0, 1) : '?' }}
                            </div>
                            <strong>{{ $txn->customer->name ?? 'N/A' }}</strong>
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

<!-- Import CSV Modal -->
<div x-show="showImportModal" x-cloak :class="{ 'show': showImportModal }" class="modal-overlay" @click.self="closeImportModal()">
    <div class="modal" @click.stop>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-bg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Import Customer Transactions</h3>
                <p style="margin: 2px 0 0; font-size: 13px; color: var(--text-secondary);">Upload a CSV file with columns: Customer, Description, In Amount, Out Amount, Payable, Receivable</p>
            </div>
        </div>
        <form action="{{ route('customers.transaction.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 20px;">
                <label for="csv_file" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px;">CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                    style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: var(--bg); color: var(--text);">
                <p style="margin: 6px 0 0; font-size: 12px; color: var(--text-secondary);">Max file size: 5MB. Accepted formats: .csv, .txt</p>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" @click="closeImportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Import
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
