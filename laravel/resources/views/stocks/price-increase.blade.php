@extends('roles.layout')

@section('title', 'Category Price Increase')

@push('head')
@include('layouts.partials.form-styles')
@endpush

@section('content')
<div class="card form-card" x-data="priceIncreaseForm()" x-init="init()">
    <div class="card-header">
        <div>
            <h2 class="card-title">Category Price Increase</h2>
            <p class="card-subtitle">Adjust the sale price of every stock record for products in a category.</p>
        </div>
        <a href="{{ route('stocks.index') }}" class="btn btn-secondary">Back to Stocks</a>
    </div>
    <div class="card-body">

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit()">
            @csrf

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Price adjustment</h3>
                        <p class="section-hint">Select a category or subcategory and the percentage to apply to its stock sale prices.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fk_category_id">Category / Subcategory <span class="required">*</span></label>
                        <select id="fk_category_id" x-model="form.fk_category_id" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <optgroup label="{{ $category->name }}">
                                    <option value="{{ $category->id }}">{{ $category->name }} (all)</option>
                                    @foreach($category->children as $child)
                                        <option value="{{ $child->id }}">&nbsp;&nbsp;{{ $child->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <span class="form-hint">Selecting a category updates its subcategories too; selecting a subcategory updates only products tagged with it.</span>
                        <span class="form-error" x-show="errors.fk_category_id" x-text="errors.fk_category_id"></span>
                    </div>
                    <div class="form-group">
                        <label for="percentage">Percentage (%) <span class="required">*</span></label>
                        <input type="number" id="percentage" x-model="form.percentage" step="0.01" min="-100" max="100"
                            placeholder="e.g. 10 or -10" required>
                        <span class="form-hint">Use a positive value to increase, or a negative value to decrease (e.g. -10 for 10% off).</span>
                        <span class="form-error" x-show="errors.percentage" x-text="errors.percentage"></span>
                    </div>
                </div>

                <div class="form-note" x-show="previewPrice !== null" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Example: a sale price of 100.00 will become <strong x-text="previewPrice"></strong>.</span>
                </div>

                <div class="form-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>This changes existing sale prices immediately, including inactive stock. Prices are rounded to two decimals and cannot go below zero.</span>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="submitting || !categoriesCount">
                    <span x-show="!submitting">Apply Price Change</span>
                    <span x-show="submitting">Applying...</span>
                </button>
                <a href="{{ route('stocks.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function priceIncreaseForm() {
        return {
            form: {
                fk_category_id: '',
                percentage: '',
            },
            errors: {},
            errorMessage: '',
            submitting: false,
            csrfToken: '',
            categoriesCount: {{ $categories->count() }},
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            get previewPrice() {
                const percentage = parseFloat(this.form.percentage);

                if (!isFinite(percentage)) {
                    return null;
                }

                return Math.max(0, 100 * (1 + percentage / 100)).toFixed(2);
            },
            async submit() {
                this.errors = {};
                this.errorMessage = '';

                if (!this.form.fk_category_id) {
                    this.errors.fk_category_id = 'Please select a category.';
                    return;
                }

                this.submitting = true;

                try {
                    const response = await fetch('{{ route('stocks.price-increase.store') }}', {
                        method: 'POST',
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
