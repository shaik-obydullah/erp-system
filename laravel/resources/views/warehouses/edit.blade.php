@extends('roles.layout')

@section('title', 'Edit Warehouse')

@section('content')
<div class="card" x-data="formHandler('{{ route('warehouses.update', $warehouse) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Warehouse</h2>
        <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Back to Warehouses</a>
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
                    <label for="name">Name *</label>
                    <input type="text" id="name" x-model="form.name" value="{{ old('name', $warehouse->name) }}" required autofocus>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
                <div class="form-group">
                    <label for="capacity">Capacity</label>
                    <input type="text" id="capacity" x-model="form.capacity" value="{{ old('capacity', $warehouse->capacity) }}">
                    <span class="form-error" x-show="errors.capacity" x-text="errors.capacity"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" x-model="form.location" value="{{ old('location', $warehouse->location) }}">
                    <span class="form-error" x-show="errors.location" x-text="errors.location"></span>
                </div>
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" x-model="form.contact_number" value="{{ old('contact_number', $warehouse->contact_number) }}">
                    <span class="form-error" x-show="errors.contact_number" x-text="errors.contact_number"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" x-model="form.email" value="{{ old('email', $warehouse->email) }}">
                    <span class="form-error" x-show="errors.email" x-text="errors.email"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Warehouse</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                name: '{{ old('name', $warehouse->name) }}',
                capacity: '{{ old('capacity', $warehouse->capacity) }}',
                location: '{{ old('location', $warehouse->location) }}',
                contact_number: '{{ old('contact_number', $warehouse->contact_number) }}',
                email: '{{ old('email', $warehouse->email) }}',
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
                        window.location.href = data.redirect || '{{ route("warehouses.index") }}';
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
