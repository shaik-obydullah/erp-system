@extends('roles.layout')

@section('title', 'Create Admin')

@section('content')
<div class="card" x-data="formHandler('{{ route('admins.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Create New Admin</h2>
        <a href="{{ route('admins.index') }}" class="btn btn-secondary">Back to Admins</a>
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
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" x-model="form.first_name" required autofocus>
                    <span class="form-error" x-show="errors.first_name" x-text="errors.first_name"></span>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" x-model="form.last_name">
                    <span class="form-error" x-show="errors.last_name" x-text="errors.last_name"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" x-model="form.email" required>
                    <span class="form-error" x-show="errors.email" x-text="errors.email"></span>
                </div>
                <div class="form-group">
                    <label for="sex">Sex *</label>
                    <select id="sex" x-model="form.sex" required>
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <span class="form-error" x-show="errors.sex" x-text="errors.sex"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" x-model="form.password" required>
                    <span class="form-error" x-show="errors.password" x-text="errors.password"></span>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password *</label>
                    <input type="password" id="password_confirmation" x-model="form.password_confirmation" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="text" id="mobile" x-model="form.mobile">
                    <span class="form-error" x-show="errors.mobile" x-text="errors.mobile"></span>
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" x-model="form.address">
                <span class="form-error" x-show="errors.address" x-text="errors.address"></span>
            </div>

            <div class="form-group">
                <label>Roles *</label>
                <span class="form-error" x-show="errors.roles" x-text="errors.roles"></span>
                <div class="permissions-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    @foreach($roles as $role)
                        <label class="permission-item">
                            <input type="checkbox" value="{{ $role->id }}" :checked="form.roles.includes({{ $role->id }})" @change="toggleRole({{ $role->id }})">
                            <span>{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Admin</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('admins.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                first_name: '',
                last_name: '',
                email: '',
                password: '',
                password_confirmation: '',
                sex: '',
                mobile: '',
                address: '',
                status: 'active',
                roles: [],
            },
            errors: {},
            errorMessage: '',
            successMessage: '',
            submitting: false,
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            toggleRole(id) {
                const index = this.form.roles.indexOf(id);
                if (index === -1) {
                    this.form.roles.push(id);
                } else {
                    this.form.roles.splice(index, 1);
                }
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
                        window.location.href = data.redirect || '{{ route("admins.index") }}';
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
