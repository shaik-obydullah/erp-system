@extends('roles.layout')

@section('title', 'Supplier Due')

@section('content')
<div class="card" x-data="dueManager()" data-search="{{ request('search') }}" data-clear-url="{{ route('suppliers.due.index') }}">
    <div class="card-header">
        <h2 class="card-title">Suppliers with Due Amount</h2>
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="text-align: right;">
                <div style="font-size: 12px; color: var(--text-muted);">Total Due</div>
                <div style="font-size: 20px; font-weight: 600; color: var(--error);">
                    {{ $currencySymbol }}{{ number_format(abs($totalDue), 2) }}
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div x-data="flashMessage()" x-show="show" x-transition x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
        {{ session('success') }}
    </div>
    @endif

    <!-- Search -->
    <div style="padding: 16px;">
        <form method="GET" action="{{ route('suppliers.due.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name, email, or mobile..." style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request('search'))
                <button type="button" class="btn btn-ghost" @click="clear()">Clear</button>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Balance</th>
                <th>Due Amount</th>
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
                    <td style="color: var(--error); font-weight: 500;">
                        {{ $currencySymbol }}{{ number_format($supplier->balance, 2) }}
                    </td>
                    <td style="color: var(--error); font-weight: 600;">
                        {{ $currencySymbol }}{{ number_format(abs($supplier->balance), 2) }}
                    </td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('suppliers.fund.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-ghost btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>
                                Add Fund
                            </a>
                            <a href="{{ route('suppliers.transaction.index', ['supplier_id' => $supplier->id]) }}" class="btn btn-ghost btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Transactions
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <div style="text-align: center; padding: 40px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="1.5" style="margin-bottom: 12px;">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <div style="font-size: 16px; font-weight: 500; color: var(--success);">No suppliers with due amounts</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">All suppliers have positive or zero balances.</div>
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
