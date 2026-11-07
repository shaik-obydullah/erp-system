@extends('roles.layout')

@section('title', 'Customer Due')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Outstanding Customer Dues</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Driven by the accounting ledger
                @if($arAccount)
                    &middot; Accounts Receivable ({{ $arAccount->code }})
                @endif
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            @if($arAccount && auth('admin')->user()?->hasPermission('accounting.advanced'))
                <a href="{{ route('accounting.ledger', ['account_id' => $arAccount->id]) }}" class="btn btn-ghost btn-sm">Open Ledger</a>
                <a href="{{ route('accounting.reconciliation') }}" class="btn btn-ghost btn-sm">Reconciliation</a>
            @endif
            <div style="text-align: right;">
                <div style="font-size: 12px; color: var(--text-muted);">Total Due (Ledger)</div>
                <div style="font-size: 20px; font-weight: 600; color: var(--error);">
                    {{ $currencySymbol }}{{ number_format($ledgerTotal, 2) }}
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success show" style="margin: 16px 16px 0;">
        {{ session('success') }}
    </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; padding: 16px;">
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Ledger Receivable</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($ledgerTotal, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Customer-attributed</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--success);">{{ $currencySymbol }}{{ number_format($customerDue, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Unassigned / walk-in</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($unassignedDue, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Invoice dues listed</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($pageTotal, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: {{ $reconciled ? 'var(--success-bg, #f0fdf4)' : 'var(--error-bg, #fef2f2)' }}; border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Reconciliation</div>
            <div style="font-size: 18px; font-weight: 600; color: {{ $reconciled ? 'var(--success)' : 'var(--error)' }};">
                {{ $reconciled ? 'Matched' : $currencySymbol.number_format($difference, 2) }}
            </div>
        </div>
    </div>

    @if(! $reconciled)
    <div class="alert alert-error show" style="margin: 0 16px 16px;">
        The invoice list does not reconcile with the ledger. Difference: {{ $currencySymbol }}{{ number_format($difference, 2) }}.
    </div>
    @endif

    @if(abs($otherReceivable) >= 0.01)
    <div style="margin: 0 16px 16px; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; color: var(--text-secondary);">
        <strong>Other ledger receivables:</strong>
        {{ $currencySymbol }}{{ number_format($otherReceivable, 2) }}
        (imports, opening balances, or other non-invoice ledger entries).
    </div>
    @endif

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('customers.due.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by invoice or customer..." style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('customers.due.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Attribution</th>
                <th>Grand Total</th>
                <th>Paid</th>
                <th>Due (Ledger)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                @php($partyCustomer = $sale->party_id ? $customers->get($sale->party_id) : null)
                @php($customer = $partyCustomer ?: $sale->customer)
                <tr>
                    <td><strong>{{ $sale->invoice_id }}</strong></td>
                    <td>
                        @if($customer)
                            <div style="font-weight: 500;">{{ $customer->name }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">{{ $customer->email }}</div>
                        @else
                            <span style="color: var(--text-muted);">Walk-in / no customer</span>
                        @endif
                    </td>
                    <td>
                        @if($sale->party_id)
                            <span class="badge badge-green">Customer</span>
                        @else
                            <span class="badge badge-orange">Unassigned</span>
                        @endif
                    </td>
                    <td>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</td>
                    <td style="color: var(--error); font-weight: 600;">
                        {{ $currencySymbol }}{{ number_format($sale->ledger_due, 2) }}
                    </td>
                    <td>
                        <span style="padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #fef3c7; color: #d97706;">
                            {{ $sale->status }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-ghost btn-sm">View</a>
                            <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-ghost btn-sm">Invoice</a>
                            @if($customer)
                                <a href="{{ route('customers.transaction.index', ['customer_id' => $customer->id]) }}" class="btn btn-ghost btn-sm">Transactions</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <div style="text-align: center; padding: 40px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="1.5" style="margin-bottom: 12px;">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <div style="font-size: 16px; font-weight: 500; color: var(--success);">No outstanding dues</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">The accounts receivable ledger has no open invoice balances.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($sales->hasPages())
    <div style="padding: 16px;">
        {{ $sales->links() }}
    </div>
    @endif
</div>
@endsection
