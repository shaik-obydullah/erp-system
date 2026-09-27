@extends('roles.layout')

@section('title', 'Edit Production Planning')

@section('content')
<div class="card" x-data="formHandler('{{ route('production-plannings.update', $planning) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Production Planning</h2>
        <a href="{{ route('production-plannings.index') }}" class="btn btn-secondary">Back to Plannings</a>
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
                    <label for="fk_bom_id">Bill of Material *</label>
                    <select id="fk_bom_id" x-model="form.fk_bom_id" required>
                        <option value="">Select BOM</option>
                        @foreach($boms as $bom)
                            <option value="{{ $bom->id }}" {{ $planning->fk_bom_id == $bom->id ? 'selected' : '' }}>{{ $bom->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_bom_id" x-text="errors.fk_bom_id"></span>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" x-model="form.quantity" value="{{ old('quantity', $planning->quantity) }}" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="production_cost">Production Cost *</label>
                    <input type="number" id="production_cost" x-model="form.production_cost" value="{{ old('production_cost', $planning->production_cost) }}" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.production_cost" x-text="errors.production_cost"></span>
                </div>
                <div class="form-group">
                    <label for="other_cost">Other Cost</label>
                    <input type="number" id="other_cost" x-model="form.other_cost" value="{{ old('other_cost', $planning->other_cost) }}" step="0.01" min="0">
                    <span class="form-error" x-show="errors.other_cost" x-text="errors.other_cost"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="expected_profit">Expected Profit</label>
                    <input type="number" id="expected_profit" x-model="form.expected_profit" value="{{ old('expected_profit', $planning->expected_profit) }}" step="0.01" min="0">
                    <span class="form-error" x-show="errors.expected_profit" x-text="errors.expected_profit"></span>
                </div>
                <div class="form-group">
                    <label>Finalized</label>
                    <div style="display: flex; align-items: center; gap: 8px; padding-top: 8px;">
                        <input type="checkbox" id="finalized" x-model="form.finalized" style="width: 18px; height: 18px;" {{ $planning->finalized ? 'checked' : '' }}>
                        <label for="finalized" style="margin: 0;">Yes, mark as finalized</label>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Planning</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('production-plannings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_bom_id: '{{ old('fk_bom_id', $planning->fk_bom_id) }}',
                production_cost: '{{ old('production_cost', $planning->production_cost) }}',
                other_cost: '{{ old('other_cost', $planning->other_cost) }}',
                expected_profit: '{{ old('expected_profit', $planning->expected_profit) }}',
                quantity: '{{ old('quantity', $planning->quantity) }}',
                finalized: {{ $planning->finalized ? 'true' : 'false' }},
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
                        window.location.href = data.redirect || '{{ route("production-plannings.index") }}';
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
