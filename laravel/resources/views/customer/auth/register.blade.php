@extends('customer.layouts.guest')

@section('content')
    <div x-data="registerForm()" x-init="init()">
        <h1 class="login-title">Create Account</h1>
        <p class="login-subtitle">Register as a new customer</p>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form method="POST" action="{{ route('customer.register') }}" @submit.prevent="submit()">
            @csrf

            <div class="input-group" :class="{ 'error': errors.name }">
                <input type="text" id="name" x-model="form.name" placeholder=" " required autofocus>
                <label for="name">Full Name</label>
                <div class="input-border"></div>
                <span class="error-message" x-text="errors.name"></span>
            </div>

            <div class="input-group" :class="{ 'error': errors.email }">
                <input type="email" id="email" x-model="form.email" placeholder=" " required autocomplete="username">
                <label for="email">Email</label>
                <div class="input-border"></div>
                <span class="error-message" x-text="errors.email"></span>
            </div>

            <div class="input-group" :class="{ 'error': errors.password }">
                <input :type="showPassword ? 'text' : 'password'" id="password" x-model="form.password" placeholder=" " required autocomplete="new-password">
                <label for="password">Password</label>
                <div class="input-border"></div>
                <button type="button" class="password-toggle" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                <span class="error-message" x-text="errors.password"></span>
            </div>

            <div class="input-group" :class="{ 'error': errors.password_confirmation }">
                <input :type="showPassword ? 'text' : 'password'" id="password_confirmation" x-model="form.password_confirmation" placeholder=" " required autocomplete="new-password">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-border"></div>
                <span class="error-message" x-text="errors.password_confirmation"></span>
            </div>

            <button type="submit" class="login-btn" :class="{ 'loading': submitting }" :disabled="submitting">
                <span class="btn-text">Create Account</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="forgot-password back-link-wrapper">
            <a href="{{ route('customer.login') }}" class="back-link">Already have an account? Sign in</a>
        </div>
    </div>

    <script>
        function registerForm() {
            return {
                form: {
                    name: '{{ old('name') }}',
                    email: '{{ old('email') }}',
                    password: '',
                    password_confirmation: '',
                },
                errors: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                },
                errorMessage: '',
                showPassword: false,
                submitting: false,
                csrfToken: '',
                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },
                async submit() {
                    this.errors = { name: '', email: '', password: '', password_confirmation: '' };
                    this.errorMessage = '';
                    this.submitting = true;

                    try {
                        const response = await fetch('{{ route('customer.register') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.form),
                        });

                        const data = await response.json();

                        if (response.ok) {
                            window.location.href = data.redirect || '/customer/dashboard';
                            return;
                        }

                        if (data.errors) {
                            this.errors = {
                                name: data.errors.name ? data.errors.name[0] : '',
                                email: data.errors.email ? data.errors.email[0] : '',
                                password: data.errors.password ? data.errors.password[0] : '',
                                password_confirmation: data.errors.password_confirmation ? data.errors.password_confirmation[0] : '',
                            };
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
