@extends('roles.layout')

@section('title', 'Customer Due')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Outstanding Customer Dues</h2>
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="text-align: right;">
                <div style="font-size: 12px; color: var(--text-muted);">Total Due</div>
                <div style="font-size: 20px; font-weight: 600; color: var(--error);">
                    {{ $currencySymbol }}{{ number_format($totalDue, 2) }}
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success show" style="margin: 16px 16px 0;">
        {{ session('success') }}
    </div>
    @endif

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('customers.due.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by invoice..." style="max-width: 400px;">
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
                <th>Type</th>
                <th>Grand Total</th>
                <th>Paid</th>
                <th>Due Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td><strong>{{ $sale->invoice_id }}</strong></td>
                    <td>{{ $sale->type }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</td>
                    <td style="color: var(--error); font-weight: 600;">
                        {{ $currencySymbol }}{{ number_format($sale->sale_due, 2) }}
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
                            <div style="font-size: 16px; font-weight: 500; color: var(--success);">No outstanding dues</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">All sales are fully paid.</div>
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
