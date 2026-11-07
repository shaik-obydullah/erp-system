@extends('roles.layout')

@section('title', 'Create Stock')

@push('head')
@include('layouts.partials.form-styles')
@endpush

@section('content')
<div class="card form-card" x-data="formHandler('{{ route('stocks.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <div>
            <h2 class="card-title">Add New Stock</h2>
            <p class="card-subtitle">Record a new inventory batch, its quantities and selling prices.</p>
        </div>
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

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Stock details</h3>
                        <p class="section-hint">Choose the product and warehouse, then record the batch quantities.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fk_product_id">Product <span class="required">*</span></label>
                        <select id="fk_product_id" x-model="form.fk_product_id" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" x-show="errors.fk_product_id" x-text="errors.fk_product_id"></span>
                    </div>
                    <div class="form-group">
                        <label for="fk_warehouses_id">Warehouse</label>
                        <select id="fk_warehouses_id" x-model="form.fk_warehouses_id">
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" x-show="errors.fk_warehouses_id" x-text="errors.fk_warehouses_id"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="batch">Batch</label>
                        <input type="text" id="batch" x-model="form.batch" placeholder="e.g. BATCH-2026-01">
                        <span class="form-hint">Optional reference used to trace this stock batch.</span>
                        <span class="form-error" x-show="errors.batch" x-text="errors.batch"></span>
                    </div>
                    <div class="form-group">
                        <label for="lot">Lot</label>
                        <input type="text" id="lot" x-model="form.lot" placeholder="e.g. LOT-4821">
                        <span class="form-hint">Optional lot number from the supplier or production run.</span>
                        <span class="form-error" x-show="errors.lot" x-text="errors.lot"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity <span class="required">*</span></label>
                        <input type="number" id="quantity" x-model="form.quantity" min="0" placeholder="e.g. 100" required>
                        <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                    </div>
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" x-model="form.status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archive">Archive</option>
                        </select>
                        <span class="form-hint">Inactive and archived stock stay recorded but are hidden from sales.</span>
                        <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Pricing &amp; VAT</h3>
                        <p class="section-hint">Set the cost and selling price, then choose how VAT is applied to this batch.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="buy_price">Buy Price <span class="required">*</span></label>
                        <input type="number" id="buy_price" x-model="form.buy_price" step="0.01" min="0" placeholder="0.00" required>
                        <span class="form-hint">Cost per unit paid to the supplier.</span>
                        <span class="form-error" x-show="errors.buy_price" x-text="errors.buy_price"></span>
                    </div>
                    <div class="form-group">
                        <label for="sale_price">Sale Price <span class="required">*</span></label>
                        <input type="number" id="sale_price" x-model="form.sale_price" step="0.01" min="0" placeholder="0.00" required>
                        <span class="form-hint">Selling price per unit, entered VAT-inclusive.</span>
                        <span class="form-error" x-show="errors.sale_price" x-text="errors.sale_price"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vat_code">VAT Code</label>
                        <select id="vat_code" x-model="form.vat_code" @change="applyVatCode()">
                            <option value="">Use company default</option>
                            <option value="standard">Standard rate (20%)</option>
                            <option value="reduced">Reduced rate (5%)</option>
                            <option value="zero">Zero rate (0%)</option>
                            <option value="exempt">Exempt</option>
                            <option value="outside_scope">Outside the scope of VAT</option>
                        </select>
                        <span class="form-hint">VAT treatment for this stock batch when it is sold.</span>
                        <span class="form-error" x-show="errors.vat_code" x-text="errors.vat_code"></span>
                    </div>
                    <div class="form-group">
                        <label for="vat_rate">VAT Rate (%)</label>
                        <input type="number" id="vat_rate" x-model="form.vat_rate" min="0" max="100" step="0.01"
                            placeholder="e.g. 20">
                        <span class="form-hint">Filled automatically from the VAT code, but can be adjusted.</span>
                        <span class="form-error" x-show="errors.vat_rate" x-text="errors.vat_rate"></span>
                    </div>
                </div>

                <div class="form-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>New stock is added to inventory at cost. It does not move cash until you buy or sell it.</span>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Stock</span>
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
                fk_product_id: '',
                fk_warehouses_id: '',
                batch: '',
                lot: '',
                quantity: '',
                buy_price: '',
                sale_price: '',
                vat_code: '',
                vat_rate: '',
                status: 'active',
            },
            vatRates: { standard: 20, reduced: 5, zero: 0, exempt: 0, outside_scope: 0 },
            applyVatCode() {
                if (this.form.vat_code && this.vatRates[this.form.vat_code] !== undefined) {
                    this.form.vat_rate = this.vatRates[this.form.vat_code];
                }
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
