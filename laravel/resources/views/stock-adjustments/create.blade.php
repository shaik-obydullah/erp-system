@extends('roles.layout')

@section('title', 'New Stock Adjustment')

@section('content')
<div class="card" x-data="formHandler('{{ route('stock-adjustments.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">New Stock Adjustment</h2>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Back to Adjustments</a>
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

            <div class="form-row">
                <div class="form-group">
                    <label for="fk_stock_id">Stock *</label>
                    <select id="fk_stock_id" x-model="form.fk_stock_id" required>
                        <option value="">Select Stock</option>
                        @foreach($stocks as $stock)
                            <option value="{{ $stock->id }}">{{ $stock->product->name ?? 'N/A' }} — Qty: {{ $stock->quantity }} ({{ $stock->batch ?? 'No batch' }})</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_stock_id" x-text="errors.fk_stock_id"></span>
                </div>
                <div class="form-group">
                    <label for="fk_warehouse_id">Warehouse</label>
                    <select id="fk_warehouse_id" x-model="form.fk_warehouse_id">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_warehouse_id" x-text="errors.fk_warehouse_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="batch">Batch</label>
                    <input type="text" id="batch" x-model="form.batch">
                    <span class="form-error" x-show="errors.batch" x-text="errors.batch"></span>
                </div>
                <div class="form-group">
                    <label for="lot">Lot</label>
                    <input type="text" id="lot" x-model="form.lot">
                    <span class="form-error" x-show="errors.lot" x-text="errors.lot"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" x-model="form.quantity" min="1" required>
                    <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                </div>
                <div class="form-group">
                    <label for="reason">Reason *</label>
                    <select id="reason" x-model="form.reason" required>
                        <option value="">Select Reason</option>
                        <option value="correction">Correction</option>
                        <option value="damage">Damage</option>
                        <option value="return">Return</option>
                    </select>
                    <span class="form-error" x-show="errors.reason" x-text="errors.reason"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Adjustment</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_stock_id: '',
                fk_warehouse_id: '',
                batch: '',
                lot: '',
                quantity: '',
                reason: '',
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
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("stock-adjustments.index") }}';
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
