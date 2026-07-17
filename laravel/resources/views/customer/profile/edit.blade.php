@extends('customer.layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="card" x-data="profileForm()" x-init="init()">
        <div class="card-header">
            <h2 class="card-title">Profile Information</h2>
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

            <form method="POST" action="{{ route('customer.profile.update') }}" @submit.prevent="submitProfile()">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <div class="input-group" :class="{ 'error': errors.name }">
                            <input type="text" id="name" x-model="form.name" placeholder=" " required>
                            <label for="name">Full Name</label>
                            <div class="input-border"></div>
                            <span class="error-message" x-text="errors.name"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group" :class="{ 'error': errors.email }">
                            <input type="email" id="email" x-model="form.email" placeholder=" " required>
                            <label for="email">Email</label>
                            <div class="input-border"></div>
                            <span class="error-message" x-text="errors.email"></span>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="phone" x-model="form.phone" placeholder=" ">
                            <label for="phone">Phone</label>
                            <div class="input-border"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <textarea id="address" x-model="form.address" placeholder=" " rows="1"></textarea>
                            <label for="address">Address</label>
                            <div class="input-border"></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="login-btn" :class="{ 'loading': submitting }" :disabled="submitting" style="max-width: 200px;">
                        <span class="btn-text">Save Changes</span>
                        <div class="spinner"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" x-data="passwordForm()">
        <div class="card-header">
            <h2 class="card-title">Update Password</h2>
        </div>
        <div class="card-body">
            <div x-show="successMessage" x-cloak class="alert alert-success show">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span x-text="successMessage"></span>
            </div>

            <form method="POST" action="{{ route('customer.password.update') }}" @submit.prevent="submitPassword()">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <div class="input-group" :class="{ 'error': errors.current_password }">
                            <input :type="showCurrentPassword ? 'text' : 'password'" id="current_password" x-model="form.current_password" placeholder=" " required autocomplete="current-password">
                            <label for="current_password">Current Password</label>
                            <div class="input-border"></div>
                            <button type="button" class="password-toggle" @click="showCurrentPassword = !showCurrentPassword" x-text="showCurrentPassword ? 'Hide' : 'Show'"></button>
                            <span class="error-message" x-text="errors.current_password"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group" :class="{ 'error': errors.password }">
                            <input :type="showPassword ? 'text' : 'password'" id="password" x-model="form.password" placeholder=" " required autocomplete="new-password">
                            <label for="password">New Password</label>
                            <div class="input-border"></div>
                            <button type="button" class="password-toggle" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                            <span class="error-message" x-text="errors.password"></span>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="input-group" :class="{ 'error': errors.password_confirmation }">
                            <input :type="showPassword ? 'text' : 'password'" id="password_confirmation" x-model="form.password_confirmation" placeholder=" " required autocomplete="new-password">
                            <label for="password_confirmation">Confirm New Password</label>
                            <div class="input-border"></div>
                            <span class="error-message" x-text="errors.password_confirmation"></span>
                        </div>
                    </div>
                    <div class="form-group"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="login-btn" :class="{ 'loading': passwordSubmitting }" :disabled="passwordSubmitting" style="max-width: 200px;">
                        <span class="btn-text">Update Password</span>
                        <div class="spinner"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function profileForm() {
            return {
                form: {
                    name: '{{ $customer->name }}',
                    email: '{{ $customer->email }}',
                    phone: '{{ $customer->phone ?? '' }}',
                    address: `{{ $customer->address ?? '' }}`,
                },
                errors: { name: '', email: '' },
                errorMessage: '',
                successMessage: '',
                submitting: false,
                csrfToken: '',
                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },
                async submitProfile() {
                    this.errors = { name: '', email: '' };
                    this.errorMessage = '';
                    this.successMessage = '';
                    this.submitting = true;

                    try {
                        const response = await fetch('{{ route('customer.profile.update') }}', {
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
                            return;
                        }

                        if (data.errors) {
                            this.errors = {
                                name: data.errors.name ? data.errors.name[0] : '',
                                email: data.errors.email ? data.errors.email[0] : '',
                            };
                        } else if (data.message) {
                            this.errorMessage = data.message;
                        }
                    } catch (e) {
                        this.errorMessage = 'An unexpected error occurred.';
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }

        function passwordForm() {
            return {
                form: {
                    current_password: '',
                    password: '',
                    password_confirmation: '',
                },
                errors: { current_password: '', password: '', password_confirmation: '' },
                errorMessage: '',
                successMessage: '',
                passwordSubmitting: false,
                showCurrentPassword: false,
                showPassword: false,
                csrfToken: '',
                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },
                async submitPassword() {
                    this.errors = { current_password: '', password: '', password_confirmation: '' };
                    this.errorMessage = '';
                    this.successMessage = '';
                    this.passwordSubmitting = true;

                    try {
                        const response = await fetch('{{ route('customer.password.update') }}', {
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
                            this.successMessage = data.message || 'Password updated successfully.';
                            this.form = { current_password: '', password: '', password_confirmation: '' };
                            return;
                        }

                        if (data.errors) {
                            this.errors = {
                                current_password: data.errors.current_password ? data.errors.current_password[0] : '',
                                password: data.errors.password ? data.errors.password[0] : '',
                                password_confirmation: data.errors.password_confirmation ? data.errors.password_confirmation[0] : '',
                            };
                        } else if (data.message) {
                            this.errorMessage = data.message;
                        }
                    } catch (e) {
                        this.errorMessage = 'An unexpected error occurred.';
                    } finally {
                        this.passwordSubmitting = false;
                    }
                },
            };
        }
    </script>
@endsection
