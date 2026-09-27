@extends('roles.layout')

@section('title', 'Sales')

@section('content')
<div x-data="deleteHandler()" x-init="init()">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Sales</h2>
        </div>

        <div x-show="successMessage" x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('sales.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by invoice ID..." style="flex: 1; min-width: 200px;">
                <select name="status" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
                <select name="type" class="form-input" style="max-width: 180px;">
                    <option value="">All Types</option>
                    <option value="pos" {{ request('type') === 'pos' ? 'selected' : '' }}>POS</option>
                    <option value="online" {{ request('type') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="manual" {{ request('type') === 'manual' ? 'selected' : '' }}>Manual</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status', 'type']))
                    <a href="{{ route('sales.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Type</th>
                    <th>Grand Total</th>
                    <th>Paid Amount</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr id="row-{{ $sale->id }}">
                        <td><strong>{{ $sale->invoice_id }}</strong></td>
                        <td><span class="badge badge-orange">{{ ucfirst($sale->type) }}</span></td>
                        <td>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</td>
                        <td>
                            <span style="color: {{ $sale->sale_due > 0 ? 'var(--error)' : 'var(--success, #22c55e)' }}; font-weight: 600;">
                                {{ $currencySymbol }}{{ number_format($sale->sale_due, 2) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $sale->status === 'paid' ? 'badge-green' : ($sale->status === 'partial' ? 'badge-orange' : 'badge-red') }}">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </a>
                                <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-ghost btn-sm" target="_blank">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    Invoice
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No sales found.</td>
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
</div>
@endsection
