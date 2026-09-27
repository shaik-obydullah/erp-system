@extends('supplier-portal.layout')

@section('title', 'Purchase Order Detail')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Purchase Order #{{ $order->order_number }}</h2>
        <a href="{{ route('supplier-portal.orders') }}" class="btn btn-secondary">Back to Orders</a>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label>Order Number</label>
                <p style="font-weight: 600;">{{ $order->order_number }}</p>
            </div>
            <div class="form-group">
                <label>Status</label>
                <span class="badge {{ $order->due_amount > 0 ? 'badge-orange' : 'badge-green' }}">
                    {{ $order->due_amount > 0 ? 'Pending Payment' : 'Paid' }}
                </span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Total Amount</label>
                <p style="font-weight: 600;">${{ number_format($order->total_amount, 2) }}</p>
            </div>
            <div class="form-group">
                <label>Due Amount</label>
                <p>${{ number_format($order->due_amount, 2) }}</p>
            </div>
        </div>

        @if($order->remarks)
        <div class="form-group">
            <label>Remarks</label>
            <p>{{ $order->remarks }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
