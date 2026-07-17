@extends('supplier.layouts.guest')

@section('content')
    <div x-data="resetPasswordForm()" x-init="init()">
        <h1 class="login-title">Reset Password</h1>
        <p class="login-subtitle">Enter your new password below.</p>

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

        <form method="POST" action="{{ route('supplier.password.store') }}" @submit.prevent="submit()">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="input-group" :class="{ 'error': errors.email }">
                <input type="email" id="email" x-model="form.email" placeholder=" " required autofocus autocomplete="username">
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
                <span class="btn-text">Reset Password</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="forgot-password back-link-wrapper">
            <a href="{{ route('supplier.login') }}" class="back-link">Back to Sign in</a>
        </div>
    </div>

    <script>
        function resetPasswordForm() {
            return {
                form: {
                    email: '{{ old('email', $request->email ?? '') }}',
                    password: '',
                    password_confirmation: '',
                },
                errors: {
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
                    this.errors = { email: '', password: '', password_confirmation: '' };
                    this.errorMessage = '';
                    this.submitting = true;

                    try {
                        const response = await fetch('{{ route('supplier.password.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                ...this.form,
                                token: '{{ $request->route('token') }}',
                            }),
                        });

                        const data = await response.json();

                        if (response.ok) {
                            window.location.href = data.redirect || '/supplier/login';
                            return;
                        }

                        if (data.errors) {
                            this.errors = {
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
