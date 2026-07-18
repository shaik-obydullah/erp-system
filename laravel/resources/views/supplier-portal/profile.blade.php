@extends('supplier-portal.layout')

@section('title', 'My Profile')

@section('content')
<div class="card" x-data="profileForm()" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Profile</h2>
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
                    <input type="text" id="name" x-model="form.name" required>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" x-model="form.email" required>
                    <span class="form-error" x-show="errors.email" x-text="errors.email"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="text" id="mobile" x-model="form.mobile">
                    <span class="form-error" x-show="errors.mobile" x-text="errors.mobile"></span>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" x-model="form.address">
                    <span class="form-error" x-show="errors.address" x-text="errors.address"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" x-model="form.password">
                    <span class="form-error" x-show="errors.password" x-text="errors.password"></span>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" x-model="form.password_confirmation">
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Profile</span>
                    <span x-show="submitting">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function profileForm() {
        return {
            form: {
                name: '{{ old('name', $supplier->name) }}',
                email: '{{ old('email', $supplier->email) }}',
                mobile: '{{ old('mobile', $supplier->mobile) }}',
                address: '{{ old('address', $supplier->address) }}',
                password: '',
                password_confirmation: '',
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
                    const response = await fetch('{{ route("supplier-portal.profile.update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-HTTP-Method-Override': 'PUT',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.successMessage = data.message || 'Profile updated successfully.';
                        this.form.password = '';
                        this.form.password_confirmation = '';
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
