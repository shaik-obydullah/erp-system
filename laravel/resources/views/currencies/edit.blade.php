@extends('roles.layout')

@section('title', 'Edit Currency')

@section('content')
<div class="card" x-data="formHandler('{{ route('currencies.update', $currency) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Currency: {{ $currency->code }}</h2>
        <a href="{{ route('currencies.index') }}" class="btn btn-secondary">Back to Currencies</a>
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
                    <label for="code">Code *</label>
                    <input type="text" id="code" x-model="form.code" placeholder="USD" required maxlength="10">
                    <span class="form-error" x-show="errors.code" x-text="errors.code"></span>
                </div>
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" x-model="form.name" placeholder="US Dollar" required maxlength="50">
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="symbol">Symbol *</label>
                    <input type="text" id="symbol" x-model="form.symbol" placeholder="$" required maxlength="10">
                    <span class="form-error" x-show="errors.symbol" x-text="errors.symbol"></span>
                </div>
                <div class="form-group">
                    <label for="exchange_rate">Exchange Rate *</label>
                    <input type="number" id="exchange_rate" x-model="form.exchange_rate" step="0.000001" min="0" required>
                    <span class="form-error" x-show="errors.exchange_rate" x-text="errors.exchange_rate"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="is_base" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="is_base" x-model="form.is_base" :true-value="1" :false-value="0">
                        Base Currency
                    </label>
                    <span class="form-error" x-show="errors.is_base" x-text="errors.is_base"></span>
                </div>
                <div class="form-group">
                    <label for="is_active" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="is_active" x-model="form.is_active" :true-value="1" :false-value="0">
                        Active
                    </label>
                    <span class="form-error" x-show="errors.is_active" x-text="errors.is_active"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Currency</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('currencies.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                code: '{{ old('code', $currency->code) }}',
                name: '{{ old('name', $currency->name) }}',
                symbol: '{{ old('symbol', $currency->symbol) }}',
                exchange_rate: '{{ old('exchange_rate', $currency->exchange_rate) }}',
                is_base: {{ $currency->is_base ? '1' : '0' }},
                is_active: {{ $currency->is_active ? '1' : '0' }},
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
                        window.location.href = data.redirect || '{{ route("currencies.index") }}';
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
