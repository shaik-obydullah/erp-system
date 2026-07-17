@extends('roles.layout')

@section('title', 'Sale Details')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Sale #{{ $sale->invoice_id }}</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-secondary" target="_blank">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Print Invoice
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">Back to Sales</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
            <div>
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Sale Information</h3>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 100px;">Invoice ID:</span> <strong>{{ $sale->invoice_id }}</strong></div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 100px;">Type:</span> <span class="badge badge-orange">{{ ucfirst($sale->type) }}</span></div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 100px;">Date:</span> {{ $sale->created_at->format('d M Y H:i') }}</div>
                    <div style="display: flex; gap: 8px;">
                        <span style="color: var(--text-secondary); min-width: 100px;">Status:</span>
                        <span class="badge {{ $sale->status === 'paid' ? 'badge-green' : ($sale->status === 'partial' ? 'badge-orange' : 'badge-red') }}">{{ ucfirst($sale->status) }}</span>
                    </div>
                </div>
            </div>
            <div>
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Payment Summary</h3>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 120px;">Grand Total:</span> <strong>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</strong></div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 120px;">Discount:</span> {{ $currencySymbol }}{{ number_format($sale->discount, 2) }}</div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 120px;">Shipping:</span> {{ $currencySymbol }}{{ number_format($sale->shipping, 2) }}</div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 120px;">Paid Amount:</span> {{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 120px;">Due Amount:</span> <strong style="color: {{ $sale->sale_due > 0 ? 'var(--error)' : 'var(--success, #22c55e)' }}">{{ $currencySymbol }}{{ number_format($sale->sale_due, 2) }}</strong></div>
                </div>
            </div>
        </div>

        <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Line Items</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sale->saleItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product->name ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                        <td><strong>{{ $currencySymbol }}{{ number_format($item->total, 2) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($sale->notes)
        <div style="margin-top: 24px; padding: 16px; background: var(--bg-secondary, #f9fafb); border-radius: 8px;">
            <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Notes</h3>
            <p style="margin: 0; font-size: 14px; color: var(--text-secondary);">{{ $sale->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
