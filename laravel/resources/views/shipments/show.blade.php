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
                        <span class="badge {{ $shipment->status === 'delivered' ? 'badge-green' : ($shipment->status === 'in_transit' ? 'badge-orange' : ($shipment->status === 'canceled' ? 'badge-red' : '')) }}">
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

<div class="card" style="margin-top: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <h2 class="card-title">Landed Costs</h2>
        <strong>Total: {{ $currencySymbol }}{{ number_format($shipment->costs->sum('amount'), 2) }}</strong>
    </div>

    @if($errors->any())
        <div class="alert alert-error show" style="margin: 16px 16px 0;">
            {{ $errors->first() }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Supplier / Carrier</th>
                <th>Date</th>
                <th>Invoice</th>
                <th style="text-align:right;">Amount</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shipment->costs as $cost)
                <tr>
                    <td><strong>{{ ucfirst($cost->cost_type) }}</strong></td>
                    <td>{{ $cost->supplier->name ?? '—' }}</td>
                    <td>{{ $cost->cost_date ? $cost->cost_date->format('d M Y') : '—' }}</td>
                    <td>{{ $cost->invoice_number ?: '—' }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ number_format($cost->amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $cost->isPaid() ? 'badge-green' : 'badge-orange' }}">
                            {{ $cost->isPaid() ? 'Paid' : 'On credit' }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            @if(!$cost->isPaid())
                                <form method="POST" action="{{ route('shipments.costs.pay', $cost) }}" style="display: flex; gap: 6px; align-items: center;">
                                    @csrf
                                    <input type="date" name="paid_date" value="{{ now()->toDateString() }}" class="form-input" style="padding: 6px 8px; font-size: 12px;">
                                    <button type="submit" class="btn btn-primary btn-sm">Mark paid</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('shipments.costs.destroy', $cost) }}" onsubmit="return confirm('Remove this cost and reverse its accounting entry?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--error);">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">No landed costs recorded for this shipment.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="card-body" style="border-top: 1px solid var(--border);">
        <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 4px;">Add a landed cost</h3>
        <p style="margin: 0 0 16px; color: var(--text-secondary); font-size: 13px;">
            The cost is debited to Inventory and credited to Cash when paid now, or to Accounts Payable when on credit.
        </p>

        <form method="POST" action="{{ route('shipments.costs.store', $shipment) }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="cost_type">Cost Type</label>
                    <select id="cost_type" name="cost_type" required>
                        <option value="freight" {{ old('cost_type', 'freight') === 'freight' ? 'selected' : '' }}>Freight</option>
                        <option value="duty" {{ old('cost_type') === 'duty' ? 'selected' : '' }}>Duty / customs</option>
                        <option value="handling" {{ old('cost_type') === 'handling' ? 'selected' : '' }}>Handling</option>
                        <option value="insurance" {{ old('cost_type') === 'insurance' ? 'selected' : '' }}>Insurance</option>
                        <option value="other" {{ old('cost_type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="0.01" step="0.01" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fk_supplier_id">Supplier / Carrier</label>
                    <select id="fk_supplier_id" name="fk_supplier_id">
                        <option value="">None</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('fk_supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_status">Payment</label>
                    <select id="payment_status" name="payment_status" required>
                        <option value="paid" {{ old('payment_status', 'paid') === 'paid' ? 'selected' : '' }}>Paid now</option>
                        <option value="unpaid" {{ old('payment_status') === 'unpaid' ? 'selected' : '' }}>On credit (pay later)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cost_date">Cost Date</label>
                    <input type="date" id="cost_date" name="cost_date" value="{{ old('cost_date', now()->toDateString()) }}" required>
                </div>
                <div class="form-group">
                    <label for="invoice_number">Invoice / Reference</label>
                    <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="e.g. Sea freight for container 12">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Add Cost</button>
        </form>
    </div>
</div>
@endsection
