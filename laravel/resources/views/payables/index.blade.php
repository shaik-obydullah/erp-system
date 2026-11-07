@extends('roles.layout')

@section('title', 'Accounts Payable')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Accounts Payable</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Driven by the accounting ledger
                @if($apAccount)
                    &middot; Accounts Payable ({{ $apAccount->code }})
                @endif
                @if($advanceAccount)
                    &middot; Advances ({{ $advanceAccount->code }})
                @endif
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            @if($apAccount && auth('admin')->user()?->hasPermission('accounting.advanced'))
                <a href="{{ route('accounting.ledger', ['account_id' => $apAccount->id]) }}" class="btn btn-ghost btn-sm">Open Ledger</a>
                <a href="{{ route('accounting.reconciliation') }}" class="btn btn-ghost btn-sm">Reconciliation</a>
            @endif
            <div style="text-align: right;">
                <div style="font-size: 12px; color: var(--text-muted);">Total Payable (Ledger)</div>
                <div style="font-size: 20px; font-weight: 600; color: var(--error);">
                    {{ $currencySymbol }}{{ number_format($payableLedger, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; padding: 16px;">
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Accounts Payable</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($payableLedger, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Advances to suppliers</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--success);">{{ $currencySymbol }}{{ number_format($advanceLedger, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Open payables</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($openPayables, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Open advances</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($openAdvances, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Unassigned payables</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($unassignedPayables, 2) }}</div>
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
        The listed documents do not reconcile with the ledger. Difference: {{ $currencySymbol }}{{ number_format($difference, 2) }}
        (typically supplier credits or overpayments).
    </div>
    @endif

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('payables.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by supplier, PO, memo, or entry..." style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('payables.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Document</th>
                <th>Supplier</th>
                <th>Description</th>
                <th style="text-align:right;">Payable</th>
                <th style="text-align:right;">Advance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payables as $item)
                @php($supplier = $item->party_id ? $suppliers->get($item->party_id) : null)
                <tr>
                    <td>{{ $item->last_date }}</td>
                    <td>
                        @if($item->order_number)
                            <strong>PO {{ $item->order_number }}</strong>
                        @elseif($item->source_type === 'supplier')
                            <strong>Supplier account</strong>
                            @if($item->source_id)
                                <div style="font-size: 12px; color: var(--text-muted);">#{{ $item->source_id }}</div>
                            @endif
                        @else
                            <strong>{{ ucfirst(str_replace('_', ' ', $item->source_type ?: 'Journal')) }}</strong>
                            @if($item->source_id)
                                #{{ $item->source_id }}
                            @endif
                        @endif
                        <div style="font-size: 12px; color: var(--text-muted);">{{ $item->entry_no }}</div>
                    </td>
                    <td>
                        @if($supplier)
                            <div style="font-weight: 500;">{{ $supplier->name }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">{{ $supplier->email }}</div>
                        @else
                            <span style="color: var(--text-muted);">Unassigned</span>
                        @endif
                    </td>
                    <td>{{ $item->description ?: $item->memo ?: '—' }}</td>
                    <td style="text-align:right; font-weight: 600; color: {{ $item->payable > 0 ? 'var(--error)' : 'var(--text-muted)' }};">
                        {{ $currencySymbol }}{{ number_format($item->payable, 2) }}
                        @if($item->payable < 0)
                            <div style="font-size: 11px; font-weight: 400;">credit</div>
                        @endif
                    </td>
                    <td style="text-align:right; font-weight: 600; color: {{ $item->advance > 0 ? 'var(--success)' : 'var(--text-muted)' }};">
                        {{ $currencySymbol }}{{ number_format($item->advance, 2) }}
                        @if($item->advance < 0)
                            <div style="font-size: 11px; font-weight: 400;">used</div>
                        @endif
                    </td>
                    <td>
                        <div class="actions">
                            @if($supplier)
                                <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-ghost btn-sm">View</a>
                                <a href="{{ route('suppliers.fund.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-ghost btn-sm">Add Fund</a>
                                <a href="{{ route('suppliers.transaction.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-ghost btn-sm">Transactions</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <div style="text-align: center; padding: 40px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="1.5" style="margin-bottom: 12px;">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <div style="font-size: 16px; font-weight: 500; color: var(--success);">No payables or advances</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">The accounts payable ledger has no open balances.</div>
                        </div>
                    </td>
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
@endsection
