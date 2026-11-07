@extends('roles.layout')

@section('title', 'Accounts Receivable')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Accounts Receivable</h2>
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
                <div style="font-size: 12px; color: var(--text-muted);">Total Receivable (Ledger)</div>
                <div style="font-size: 20px; font-weight: 600; color: var(--success, #22c55e);">
                    {{ $currencySymbol }}{{ number_format($ledgerTotal, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; padding: 16px;">
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Ledger Receivable</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($ledgerTotal, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Open receivables</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($openTotal, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Customer-attributed</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--success);">{{ $currencySymbol }}{{ number_format($customerTotal, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Unassigned / walk-in</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($unassignedTotal, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Credit balances (advances)</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format(abs($creditTotal), 2) }}</div>
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
        The open items do not reconcile with the ledger. Difference: {{ $currencySymbol }}{{ number_format($difference, 2) }}.
    </div>
    @endif

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('receivables.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by invoice, memo, or entry..." style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('receivables.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Document</th>
                <th>Customer</th>
                <th>Description</th>
                <th style="text-align:right;">Debit</th>
                <th style="text-align:right;">Credit</th>
                <th style="text-align:right;">Outstanding</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receivables as $item)
                @php($customer = $item->party_id ? $customers->get($item->party_id) : null)
                @php($saleId = $item->source_type === 'sale' ? $item->source_id : null)
                <tr>
                    <td>{{ $item->last_date }}</td>
                    <td>
                        @if($item->invoice_id)
                            <strong>{{ $item->invoice_id }}</strong>
                        @else
                            <strong>{{ ucfirst(str_replace('_', ' ', $item->source_type ?: 'Journal')) }}</strong>
                            @if($item->source_id)
                                #{{ $item->source_id }}
                            @endif
                        @endif
                        <div style="font-size: 12px; color: var(--text-muted);">{{ $item->entry_no }}</div>
                    </td>
                    <td>
                        @if($customer)
                            <div style="font-weight: 500;">{{ $customer->name }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">{{ $customer->email }}</div>
                        @else
                            <span style="color: var(--text-muted);">Unassigned / walk-in</span>
                        @endif
                    </td>
                    <td>{{ $item->description ?: $item->memo ?: '—' }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($item->total_debit, 2) }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($item->total_credit, 2) }}</td>
                    <td style="text-align:right; font-weight: 600; color: {{ $item->outstanding > 0 ? 'var(--error)' : 'var(--success)' }};">
                        {{ $currencySymbol }}{{ number_format($item->outstanding, 2) }}
                        @if($item->outstanding < 0)
                            <div style="font-size: 11px; font-weight: 400;">credit balance</div>
                        @endif
                    </td>
                    <td>
                        <div class="actions">
                            @if($saleId)
                                <a href="{{ route('sales.show', $saleId) }}" class="btn btn-ghost btn-sm">View</a>
                                <a href="{{ route('sales.invoice', $saleId) }}" class="btn btn-ghost btn-sm">Invoice</a>
                            @endif
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
                            <div style="font-size: 16px; font-weight: 500; color: var(--success);">No open receivables</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">The accounts receivable ledger has no open balances.</div>
                        </div>
                    </td>
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
@endsection
