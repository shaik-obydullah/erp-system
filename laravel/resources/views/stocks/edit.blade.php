@extends('roles.layout')

@section('title', 'Edit Stock')

@section('content')
<div class="card" x-data="formHandler('{{ route('stocks.update', $stock) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Stock</h2>
        <a href="{{ route('stocks.index') }}" class="btn btn-secondary">Back to Stocks</a>
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
                    <label for="fk_product_id">Product *</label>
                    <select id="fk_product_id" x-model="form.fk_product_id" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ $stock->fk_product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_product_id" x-text="errors.fk_product_id"></span>
                </div>
                <div class="form-group">
                    <label for="fk_warehouses_id">Warehouse</label>
                    <select id="fk_warehouses_id" x-model="form.fk_warehouses_id">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $stock->fk_warehouses_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_warehouses_id" x-text="errors.fk_warehouses_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="batch">Batch</label>
                    <input type="text" id="batch" x-model="form.batch" value="{{ old('batch', $stock->batch) }}">
                    <span class="form-error" x-show="errors.batch" x-text="errors.batch"></span>
                </div>
                <div class="form-group">
                    <label for="lot">Lot</label>
                    <input type="text" id="lot" x-model="form.lot" value="{{ old('lot', $stock->lot) }}">
                    <span class="form-error" x-show="errors.lot" x-text="errors.lot"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" x-model="form.quantity" min="0" required>
                    <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="buy_price">Buy Price *</label>
                    <input type="number" id="buy_price" x-model="form.buy_price" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.buy_price" x-text="errors.buy_price"></span>
                </div>
                <div class="form-group">
                    <label for="sale_price">Sale Price *</label>
                    <input type="number" id="sale_price" x-model="form.sale_price" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.sale_price" x-text="errors.sale_price"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="active" {{ $stock->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $stock->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="archive" {{ $stock->status == 'archive' ? 'selected' : '' }}>Archive</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Stock</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('stocks.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_product_id: '{{ old('fk_product_id', $stock->fk_product_id) }}',
                fk_warehouses_id: '{{ old('fk_warehouses_id', $stock->fk_warehouses_id) }}',
                batch: '{{ old('batch', $stock->batch) }}',
                lot: '{{ old('lot', $stock->lot) }}',
                quantity: '{{ old('quantity', $stock->quantity) }}',
                buy_price: '{{ old('buy_price', $stock->buy_price) }}',
                sale_price: '{{ old('sale_price', $stock->sale_price) }}',
                status: '{{ old('status', $stock->status) }}',
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
                        window.location.href = data.redirect || '{{ route("stocks.index") }}';
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
