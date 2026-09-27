@extends('roles.layout')

@section('title', 'Create Sale Return')

@section('content')
<div class="card" x-data="formHandler('{{ route('sale-returns.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Add Sale Return</h2>
        <a href="{{ route('sale-returns.index') }}" class="btn btn-secondary">Back to Returns</a>
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
                    <label for="fk_sale_id">Sale Invoice *</label>
                    <select id="fk_sale_id" x-model="form.fk_sale_id" required>
                        <option value="">Select Sale</option>
                        @foreach($sales as $sale)
                            <option value="{{ $sale->id }}">{{ $sale->invoice_id }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_sale_id" x-text="errors.fk_sale_id"></span>
                </div>
                <div class="form-group">
                    <label for="fk_stock_id">Product / Stock *</label>
                    <select id="fk_stock_id" x-model="form.fk_stock_id" required>
                        <option value="">Select Product</option>
                        @foreach($stocks as $stock)
                            <option value="{{ $stock->id }}">{{ $stock->product->name ?? 'Product' }} (Qty: {{ $stock->quantity }})</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_stock_id" x-text="errors.fk_stock_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" x-model="form.quantity" min="1" required>
                    <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                </div>
                <div class="form-group">
                    <label for="refund_amount">Refund Amount *</label>
                    <input type="number" id="refund_amount" x-model="form.refund_amount" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.refund_amount" x-text="errors.refund_amount"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="reason">Reason *</label>
                    <select id="reason" x-model="form.reason" required>
                        <option value="customer_request">Customer Request</option>
                        <option value="damaged">Damaged</option>
                        <option value="wrong_item">Wrong Item</option>
                        <option value="defective">Defective</option>
                        <option value="other">Other</option>
                    </select>
                    <span class="form-error" x-show="errors.reason" x-text="errors.reason"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="note">Note</label>
                    <textarea id="note" x-model="form.note" rows="3"></textarea>
                    <span class="form-error" x-show="errors.note" x-text="errors.note"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Return</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('sale-returns.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_sale_id: '',
                fk_stock_id: '',
                quantity: 1,
                refund_amount: 0,
                reason: 'customer_request',
                note: '',
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
                        window.location.href = data.redirect || '{{ route("sale-returns.index") }}';
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
