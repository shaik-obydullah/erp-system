@extends('roles.layout')

@section('title', 'Shipment Details')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Shipment #{{ $shipment->tracking_number }}</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('shipments.edit', $shipment) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('shipments.index') }}" class="btn btn-secondary">Back to Shipments</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div>
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Shipment Information</h3>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Tracking Number:</span> <strong>{{ $shipment->tracking_number }}</strong></div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Status:</span>
                        <span class="badge {{ $shipment->status === 'delivered' ? 'badge-green' : ($shipment->status === 'in_transit' ? 'badge-orange' : ($shipment->status === 'cancelled' ? 'badge-red' : '')) }}">
                            {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                        </span>
                    </div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Received Date:</span> {{ $shipment->received_date ? \Carbon\Carbon::parse($shipment->received_date)->format('d M Y') : '—' }}</div>
                </div>
            </div>
            <div>
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Related Info</h3>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Purchase Order:</span> {{ $shipment->purchaseOrder->order_number ?? '—' }}</div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Warehouse:</span> {{ $shipment->warehouse->name ?? '—' }}</div>
                </div>
            </div>
        </div>

        @if($shipment->remark)
        <div style="margin-top: 24px; padding: 16px; background: var(--bg-secondary, #f9fafb); border-radius: 8px;">
            <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Remarks</h3>
            <p style="margin: 0; font-size: 14px; color: var(--text-secondary);">{{ $shipment->remark }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
