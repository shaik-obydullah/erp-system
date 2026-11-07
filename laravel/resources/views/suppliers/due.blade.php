@extends('roles.layout')

@section('title', 'Supplier Due')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Supplier Due &amp; Advances</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Driven by the accounting ledger &middot; includes inactive and deleted suppliers
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
                    {{ $currencySymbol }}{{ number_format($ledgerPayable, 2) }}
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
            <div style="font-size: 12px; color: var(--text-muted);">Accounts Payable</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($ledgerPayable, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Advances to suppliers</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--success);">{{ $currencySymbol }}{{ number_format($ledgerAdvances, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Supplier-attributed payable</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($supplierPayable, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Unassigned payables</div>
            <div style="font-size: 18px; font-weight: 600; color: var(--error);">{{ $currencySymbol }}{{ number_format($unassignedPayable, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Net ledger position</div>
            <div style="font-size: 18px; font-weight: 600; color: {{ $ledgerPosition < 0 ? 'var(--error)' : 'var(--success)' }};">{{ $currencySymbol }}{{ number_format($ledgerPosition, 2) }}</div>
        </div>
        <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">Operational balances</div>
            <div style="font-size: 18px; font-weight: 600;">{{ $currencySymbol }}{{ number_format($operationalPosition, 2) }}</div>
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
        The ledger position and the operational supplier balances disagree by
        {{ $currencySymbol }}{{ number_format($difference, 2) }}. A balance change may not have been posted to accounting.
    </div>
    @endif

    @if(abs($unassignedPayable) >= 0.01)
    <div style="margin: 0 16px 16px; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; color: var(--text-secondary);">
        <strong>Unassigned payables:</strong>
        {{ $currencySymbol }}{{ number_format($unassignedPayable, 2) }}
        (ledger entries with no supplier on the source document). They are listed on Accounts Payable.
    </div>
    @endif

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('suppliers.due.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name, email, or mobile..." style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('suppliers.due.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Status</th>
                <th style="text-align:right;">Payable</th>
                <th style="text-align:right;">Advance</th>
                <th style="text-align:right;">Net Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: var(--error); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                {{ substr($supplier->name, 0, 1) }}
                            </div>
                            <strong>{{ $supplier->name }}</strong>
                        </div>
                    </td>
                    <td>{{ $supplier->email }}</td>
                    <td>{{ $supplier->mobile ?? '—' }}</td>
                    <td>
                        @if($supplier->trashed())
                            <span style="padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #fee2e2; color: #b91c1c;">deleted</span>
                        @else
                            <span style="padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; background: {{ $supplier->status === 'active' ? '#dcfce7' : '#f1f5f9' }}; color: {{ $supplier->status === 'active' ? '#15803d' : '#475569' }};">
                                {{ $supplier->status }}
                            </span>
                        @endif
                    </td>
                    <td style="text-align:right; font-weight: 600; color: {{ $supplier->ledger_payable > 0 ? 'var(--error)' : 'var(--text-muted)' }};">
                        {{ $currencySymbol }}{{ number_format((float) $supplier->ledger_payable, 2) }}
                        @if($supplier->ledger_payable < 0)
                            <div style="font-size: 11px; font-weight: 400;">credit</div>
                        @endif
                    </td>
                    <td style="text-align:right; font-weight: 600; color: {{ $supplier->ledger_advance > 0 ? 'var(--success)' : 'var(--text-muted)' }};">
                        {{ $currencySymbol }}{{ number_format((float) $supplier->ledger_advance, 2) }}
                        @if($supplier->ledger_advance < 0)
                            <div style="font-size: 11px; font-weight: 400;">used</div>
                        @endif
                    </td>
                    <td style="text-align:right; font-weight: 600; color: {{ ((float) $supplier->ledger_advance - (float) $supplier->ledger_payable) < 0 ? 'var(--error)' : 'var(--success)' }};">
                        {{ $currencySymbol }}{{ number_format((float) $supplier->ledger_advance - (float) $supplier->ledger_payable, 2) }}
                    </td>
                    <td>
                        <div class="actions">
                            @if(! $supplier->trashed())
                                <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-ghost btn-sm">View</a>
                                <a href="{{ route('suppliers.fund.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-ghost btn-sm">Add Fund</a>
                                <a href="{{ route('suppliers.transaction.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-ghost btn-sm">Transactions</a>
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
                            <div style="font-size: 16px; font-weight: 500; color: var(--success);">No supplier ledger balances</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">The accounts payable and supplier advance ledgers have no open balances.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($suppliers->hasPages())
    <div style="padding: 16px;">
        {{ $suppliers->links() }}
    </div>
    @endif
</div>
@endsection
