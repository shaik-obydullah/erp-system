@extends('roles.layout')

@section('title', 'Create Income')

@section('content')
<div class="card" x-data="formHandler('{{ route('incomes.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Add Income</h2>
        <a href="{{ route('incomes.index') }}" class="btn btn-secondary">Back to Incomes</a>
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
                    <label for="description">Description *</label>
                    <input type="text" id="description" x-model="form.description" required autofocus>
                    <span class="form-error" x-show="errors.description" x-text="errors.description"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="amount">Amount (VAT inclusive) *</label>
                    <input type="number" id="amount" x-model="form.amount" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.amount" x-text="errors.amount"></span>
                    <p x-show="form.amount > 0" x-cloak style="margin-top:6px; font-size:13px; color:var(--text-secondary);">
                        Your books will record:
                        <strong x-text="'Cash increases {{ $currencySymbol }}' + Number(form.amount || 0).toFixed(2)"></strong>
                        ·
                        <strong x-text="'Income increases {{ $currencySymbol }}' + Number(form.amount || 0).toFixed(2)"></strong>
                    </p>
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
                    <span class="form-error" x-show="errors.vat_code" x-text="errors.vat_code"></span>
                </div>
                <div class="form-group">
                    <label for="vat_rate">VAT Rate (%)</label>
                    <input type="number" id="vat_rate" x-model="form.vat_rate" min="0" max="100" step="0.01" placeholder="e.g. 20">
                    <span class="form-error" x-show="errors.vat_rate" x-text="errors.vat_rate"></span>
                </div>
            </div>

            <div class="form-row" x-show="parseFloat(form.amount) > 0">
                <div class="form-group">
                    <small class="form-hint">
                        Net: <strong x-text="'{{ $currencySymbol }}' + vatNet.toFixed(2)"></strong> ·
                        Output VAT: <strong x-text="'{{ $currencySymbol }}' + vatAmount.toFixed(2)"></strong>
                    </small>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Add Income</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('incomes.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                description: '',
                amount: '',
                vat_code: '',
                vat_rate: '',
            },
            vatRates: { standard: 20, reduced: 5, zero: 0, exempt: 0, outside_scope: 0 },
            applyVatCode() {
                if (this.form.vat_code && this.vatRates[this.form.vat_code] !== undefined) {
                    this.form.vat_rate = this.vatRates[this.form.vat_code];
                }
            },
            get vatRateValue() {
                return this.form.vat_rate !== '' && this.form.vat_rate !== null ? parseFloat(this.form.vat_rate) || 0 : 20;
            },
            get vatNet() {
                return Math.round((parseFloat(this.form.amount) || 0) / (1 + this.vatRateValue / 100) * 100) / 100;
            },
            get vatAmount() {
                return Math.round(((parseFloat(this.form.amount) || 0) - this.vatNet) * 100) / 100;
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
                        window.location.href = data.redirect || '{{ route("incomes.index") }}';
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
