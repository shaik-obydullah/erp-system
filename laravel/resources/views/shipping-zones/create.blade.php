@extends('roles.layout')

@section('title', 'Create Shipping Zone')

@section('content')
<div class="card" x-data="formHandler('{{ route('shipping-zones.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Create New Shipping Zone</h2>
        <a href="{{ route('shipping-zones.index') }}" class="btn btn-secondary">Back to Zones</a>
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
                    <label for="name">Zone Name *</label>
                    <input type="text" id="name" x-model="form.name" required autofocus placeholder="e.g. United States, Europe">
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" x-model="form.sort_order" placeholder="0">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" x-model="form.status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label>Countries *</label>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 0 0 8px;">
                    Select the countries this zone applies to.
                </p>
                <div style="max-height: 260px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 12px;">
                    <label class="permission-item" style="border-bottom: 1px solid var(--border); margin-bottom: 8px; padding-bottom: 8px;">
                        <input type="checkbox" x-model="form.rest_of_world" value="1">
                        <span><strong>Rest of World</strong> <span style="color: var(--text-secondary); font-size: 12px;">Applies to all other countries</span></span>
                    </label>
                    <template x-for="(name, code) in countries" :key="code">
                        <label class="permission-item">
                            <input type="checkbox" :value="code" x-model="form.countries">
                            <span x-text="name"></span>
                        </label>
                    </template>
                </div>
                <span class="form-error" x-show="errors.countries" x-text="errors.countries"></span>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Zone</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('shipping-zones.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                name: '',
                status: 'active',
                sort_order: 0,
                countries: [],
                rest_of_world: false,
            },
            countries: @json($countries),
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
                        window.location.href = data.redirect || '{{ route("shipping-zones.index") }}';
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