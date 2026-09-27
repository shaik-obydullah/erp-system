@extends('roles.layout')

@section('title', 'Create Purchase Order')

@section('content')
<div class="card" x-data="formHandler('{{ route('purchase-orders.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Create Purchase Order</h2>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Back to Orders</a>
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
                    <label for="fk_need_id">Need</label>
                    <select id="fk_need_id" x-model="form.fk_need_id">
                        <option value="">Select Need</option>
                        @foreach($needs as $need)
                            <option value="{{ $need->id }}">{{ $need->description }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_need_id" x-text="errors.fk_need_id"></span>
                </div>
                <div class="form-group">
                    <label for="fk_supplier_id">Supplier *</label>
                    <select id="fk_supplier_id" x-model="form.fk_supplier_id" required>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_supplier_id" x-text="errors.fk_supplier_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="order_number">Order Number *</label>
                    <input type="text" id="order_number" x-model="form.order_number" required>
                    <span class="form-error" x-show="errors.order_number" x-text="errors.order_number"></span>
                </div>
                <div class="form-group">
                    <label for="total_amount">Total Amount *</label>
                    <input type="number" id="total_amount" x-model="form.total_amount" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.total_amount" x-text="errors.total_amount"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="due_amount">Due Amount</label>
                    <input type="number" id="due_amount" x-model="form.due_amount" step="0.01" min="0">
                    <span class="form-error" x-show="errors.due_amount" x-text="errors.due_amount"></span>
                </div>
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" x-model="form.remarks" rows="3"></textarea>
                    <span class="form-error" x-show="errors.remarks" x-text="errors.remarks"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Purchase Order</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_need_id: '',
                fk_supplier_id: '',
                order_number: '',
                total_amount: '',
                due_amount: '',
                remarks: '',
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
                        window.location.href = data.redirect || '{{ route("purchase-orders.index") }}';
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
