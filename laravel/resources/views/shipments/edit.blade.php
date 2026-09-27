@extends('roles.layout')

@section('title', 'Edit Shipment')

@section('content')
<div class="card" x-data="formHandler('{{ route('shipments.update', $shipment) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Shipment</h2>
        <a href="{{ route('shipments.index') }}" class="btn btn-secondary">Back to Shipments</a>
    </div>
    <div class="card-body">

        <div x-show="successMessage" x-cloak class="alert alert-success show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit($event)">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label for="fk_purchase_order_id">Purchase Order *</label>
                    <select id="fk_purchase_order_id" x-model="form.fk_purchase_order_id" required>
                        <option value="">Select PO</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}" {{ $shipment->fk_purchase_order_id == $po->id ? 'selected' : '' }}>{{ $po->order_number }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_purchase_order_id" x-text="errors.fk_purchase_order_id"></span>
                </div>
                <div class="form-group">
                    <label for="fk_warehouse_id">Warehouse *</label>
                    <select id="fk_warehouse_id" x-model="form.fk_warehouse_id" required>
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $shipment->fk_warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_warehouse_id" x-text="errors.fk_warehouse_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tracking_number">Tracking Number *</label>
                    <input type="text" id="tracking_number" x-model="form.tracking_number" value="{{ old('tracking_number', $shipment->tracking_number) }}" required autofocus>
                    <span class="form-error" x-show="errors.tracking_number" x-text="errors.tracking_number"></span>
                </div>
                <div class="form-group">
                    <label for="received_date">Received Date</label>
                    <input type="date" id="received_date" x-model="form.received_date" value="{{ old('received_date', $shipment->received_date) }}">
                    <span class="form-error" x-show="errors.received_date" x-text="errors.received_date"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="pending" {{ $shipment->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_transit" {{ $shipment->status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="delivered" {{ $shipment->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ $shipment->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="remark">Remark</label>
                    <textarea id="remark" x-model="form.remark" rows="3">{{ old('remark', $shipment->remark) }}</textarea>
                    <span class="form-error" x-show="errors.remark" x-text="errors.remark"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Shipment</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('shipments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_purchase_order_id: '{{ old('fk_purchase_order_id', $shipment->fk_purchase_order_id) }}',
                fk_warehouse_id: '{{ old('fk_warehouse_id', $shipment->fk_warehouse_id) }}',
                tracking_number: '{{ old('tracking_number', $shipment->tracking_number) }}',
                received_date: '{{ old('received_date', $shipment->received_date) }}',
                status: '{{ old('status', $shipment->status) }}',
                remark: '{{ old('remark', $shipment->remark) }}',
            },
            errors: {},
            errorMessage: '',
            successMessage: '',
            submitting: false,
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            async submit() {
                this.errors = {};
                this.errorMessage = '';
                this.successMessage = '';
                this.submitting = true;
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-HTTP-Method-Override': method,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("shipments.index") }}';
                        return;
                    }
                    if (data.errors) {
                        this.errors = {};
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    } else if (data.message) {
                        this.errorMessage = data.message;
                    }
                } catch (e) {
                    this.errorMessage = 'An unexpected error occurred. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
@endsection
