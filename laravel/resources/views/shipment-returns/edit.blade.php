@extends('roles.layout')

@section('title', 'Edit Shipment Return')

@section('content')
<div class="card" x-data="formHandler('{{ route('shipment-returns.update', $shipmentReturn) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Shipment Return</h2>
        <a href="{{ route('shipment-returns.index') }}" class="btn btn-secondary">Back to Returns</a>
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
                            <option value="{{ $po->id }}" {{ $shipmentReturn->fk_purchase_order_id == $po->id ? 'selected' : '' }}>{{ $po->order_number }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_purchase_order_id" x-text="errors.fk_purchase_order_id"></span>
                </div>
                <div class="form-group">
                    <label for="invoice_amount">Invoice Amount *</label>
                    <input type="number" id="invoice_amount" x-model="form.invoice_amount" value="{{ old('invoice_amount', $shipmentReturn->invoice_amount) }}" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.invoice_amount" x-text="errors.invoice_amount"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="return_reason">Return Reason *</label>
                    <textarea id="return_reason" x-model="form.return_reason" rows="3" required>{{ old('return_reason', $shipmentReturn->return_reason) }}</textarea>
                    <span class="form-error" x-show="errors.return_reason" x-text="errors.return_reason"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="pending" {{ $shipmentReturn->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $shipmentReturn->status == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $shipmentReturn->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="remark">Remark</label>
                    <textarea id="remark" x-model="form.remark" rows="3">{{ old('remark', $shipmentReturn->remark) }}</textarea>
                    <span class="form-error" x-show="errors.remark" x-text="errors.remark"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Return</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('shipment-returns.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_purchase_order_id: '{{ old('fk_purchase_order_id', $shipmentReturn->fk_purchase_order_id) }}',
                invoice_amount: '{{ old('invoice_amount', $shipmentReturn->invoice_amount) }}',
                return_reason: '{{ old('return_reason', $shipmentReturn->return_reason) }}',
                status: '{{ old('status', $shipmentReturn->status) }}',
                remark: '{{ old('remark', $shipmentReturn->remark) }}',
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
                        window.location.href = data.redirect || '{{ route("shipment-returns.index") }}';
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
