@extends('customer-portal.layout')

@section('title', 'Order Detail')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Order #{{ $order->invoice_id }}</h2>
        <a href="{{ route('portal.orders') }}" class="btn btn-secondary">Back to Orders</a>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label>Invoice ID</label>
                <p style="font-weight: 600;">{{ $order->invoice_id }}</p>
            </div>
            <div class="form-group">
                <label>Type</label>
                <p>{{ ucfirst($order->type ?? 'sale') }}</p>
            </div>
            <div class="form-group">
                <label>Status</label>
                <span class="badge {{ $order->status === 'completed' ? 'badge-green' : ($order->status === 'pending' ? 'badge-orange' : 'badge-blue') }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Grand Total</label>
                <p style="font-weight: 600;">${{ number_format($order->grand_total, 2) }}</p>
            </div>
            <div class="form-group">
                <label>Paid Amount</label>
                <p>${{ number_format($order->paid_amount, 2) }}</p>
            </div>
            <div class="form-group">
                <label>Due Amount</label>
                <p>${{ number_format($order->sale_due, 2) }}</p>
            </div>
        </div>

        @if($order->note)
        <div class="form-group">
            <label>Note</label>
            <p>{{ $order->note }}</p>
        </div>
        @endif
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="card-header">
        <h2 class="card-title">Order Items</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Size</th>
                <th>Color</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->details as $detail)
                <tr>
                    <td><strong>{{ $detail->stock_name }}</strong></td>
                    <td>{{ $detail->size ?? '-' }}</td>
                    <td>{{ $detail->color ?? '-' }}</td>
                    <td>{{ $detail->sale_stock }}</td>
                    <td>${{ number_format($detail->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">No items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
