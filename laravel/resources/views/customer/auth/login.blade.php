@extends('customer.layouts.guest')

@section('content')
    <div x-data="loginForm()" x-init="init()">
        <h1 class="login-title">Sign in</h1>
        <p class="login-subtitle">Use your Customer Account</p>

        @if (session('status'))
            <div class="alert alert-success show">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form method="POST" action="{{ route('customer.login') }}" @submit.prevent="submit()">
            @csrf

            <div class="input-group" :class="{ 'error': errors.email }">
                <input type="email" id="email" x-model="form.email" placeholder=" " required autofocus autocomplete="username">
                <label for="email">Email</label>
                <div class="input-border"></div>
                <span class="error-message" x-text="errors.email"></span>
            </div>

            <div class="input-group" :class="{ 'error': errors.password }">
                <input :type="showPassword ? 'text' : 'password'" id="password" x-model="form.password" placeholder=" " required autocomplete="current-password">
                <label for="password">Password</label>
                <div class="input-border"></div>
                <button type="button" class="password-toggle" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                <span class="error-message" x-text="errors.password"></span>
            </div>

            <div class="forgot-password">
                @if (Route::has('customer.password.request'))
                    <a href="{{ route('customer.password.request') }}">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="login-btn" :class="{ 'loading': submitting }" :disabled="submitting">
                <span class="btn-text">Sign in</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="forgot-password back-link-wrapper">
            <a href="{{ route('customer.register') }}" class="back-link">Create an account</a>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                form: {
                    email: '{{ old('email') }}',
                    password: '',
                },
                errors: {
                    email: '',
                    password: '',
                },
                errorMessage: '',
                showPassword: false,
                submitting: false,
                csrfToken: '',
                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },
                async submit() {
                    this.errors = { email: '', password: '' };
                    this.errorMessage = '';
                    this.submitting = true;

                    try {
                        const response = await fetch('{{ route('customer.login') }}', {
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
                                email: data.errors.email ? data.errors.email[0] : '',
                                password: data.errors.password ? data.errors.password[0] : '',
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
