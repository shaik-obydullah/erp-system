@extends('roles.layout')

@section('title', 'Edit Bill of Material')

@section('content')
<div class="card" x-data="formHandler('{{ route('bill-of-materials.update', $bom) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Bill of Material</h2>
        <a href="{{ route('bill-of-materials.index') }}" class="btn btn-secondary">Back to BOMs</a>
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
                            <option value="{{ $product->id }}" {{ $bom->fk_product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_product_id" x-text="errors.fk_product_id"></span>
                </div>
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" x-model="form.name" value="{{ old('name', $bom->name) }}" required autofocus>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fk_unit_id">Unit *</label>
                    <select id="fk_unit_id" x-model="form.fk_unit_id" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ $bom->fk_unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_unit_id" x-text="errors.fk_unit_id"></span>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" x-model="form.quantity" value="{{ old('quantity', $bom->quantity) }}" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" x-model="form.description" rows="4">{{ old('description', $bom->description) }}</textarea>
                    <span class="form-error" x-show="errors.description" x-text="errors.description"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update BOM</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('bill-of-materials.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_product_id: '{{ old('fk_product_id', $bom->fk_product_id) }}',
                name: '{{ old('name', $bom->name) }}',
                fk_unit_id: '{{ old('fk_unit_id', $bom->fk_unit_id) }}',
                quantity: '{{ old('quantity', $bom->quantity) }}',
                description: '{{ old('description', $bom->description) }}',
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
                        window.location.href = data.redirect || '{{ route("bill-of-materials.index") }}';
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
